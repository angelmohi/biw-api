<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Models\Transaction;
use App\Models\BiwengerUser;
use App\Models\BiwengerUserBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateLeagues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leagues:update 
                            {--league=* : ID específico de liga a actualizar}
                            {--timeout=300 : Timeout en segundos para cada liga}
                            {--force : Forzar actualización aunque haya errores}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las estadísticas y datos de todas las ligas desde la API de Biwenger';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('🚀 Iniciando actualización de ligas...');

        $leagueIds = $this->option('league');
        $timeout = (int) $this->option('timeout');
        $force = $this->option('force');

        if (!empty($leagueIds)) {
            $leagues = League::whereIn('id', $leagueIds)->get();
            Log::info("📋 Actualizando " . count($leagues) . " liga(s) específica(s)", ['league_ids' => $leagueIds]);
        } else {
            $leagues = League::all();
            Log::info("📋 Actualizando todas las ligas (" . count($leagues) . " total)");
        }

        if ($leagues->isEmpty()) {
            Log::warning('⚠️  No se encontraron ligas para actualizar.');
            return 0;
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($leagues as $league) {
            Log::info("Iniciando actualización de liga: {$league->name}", ['league_id' => $league->id]);
            
            try {
                if ($timeout > 0) {
                    set_time_limit($timeout);
                }

                $startTime = microtime(true);
                
                $result = $league->refreshStatistics();
                
                $endTime = microtime(true);
                $duration = round($endTime - $startTime, 2);

                if ($result) {
                    $successCount++;
                    
                    $this->cleanupDuplicateTransactionsForLeague($league);
                    
                    $league->touch();
                    
                    Log::info("✅ Liga '{$league->name}' actualizada correctamente ({$duration}s)", [
                        'league_id' => $league->id,
                        'duration' => $duration,
                        'status' => 'success',
                        'updated_at' => $league->updated_at->toISOString()
                    ]);
                } else {
                    $errorCount++;
                    $errorMessage = "Liga '{$league->name}': Error en refreshStatistics()";
                    $errors[] = $errorMessage;
                    Log::error("❌ Error actualizando liga '{$league->name}'", [
                        'league_id' => $league->id,
                        'error' => 'refreshStatistics returned false'
                    ]);
                    
                    if (!$force) {
                        Log::error("💥 Deteniendo actualización. Usa --force para continuar con errores.");
                        break;
                    }
                }

            } catch (\Exception $e) {
                $errorCount++;
                $errorMessage = "Liga '{$league->name}': " . $e->getMessage();
                $errors[] = $errorMessage;
                Log::error("❌ Excepción en liga '{$league->name}': " . $e->getMessage(), [
                    'league_id' => $league->id,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                if (!$force) {
                    Log::error("💥 Deteniendo actualización. Usa --force para continuar con errores.");
                    break;
                }
            }
        }

        $totalProcessed = $successCount + $errorCount;
        $successRate = $totalProcessed > 0 ? round(($successCount / $totalProcessed) * 100, 2) : 0;
        
        Log::info('📊 Resumen de la actualización:', [
            'ligas_procesadas' => $totalProcessed,
            'exitosas' => $successCount,
            'errores' => $errorCount,
            'tasa_exito' => $successRate . '%'
        ]);

        if (!empty($errors)) {
            Log::error('🚨 Errores encontrados durante la actualización:', ['errors' => $errors]);
        }

        if ($successCount > 0) {
            Log::info('🎉 Actualización completada!', [
                'total_exitosas' => $successCount,
                'total_errores' => $errorCount
            ]);
        }

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Remove duplicate transactions for a league and update affected user balances
     */
    private function cleanupDuplicateTransactionsForLeague(League $league): void
    {
        Log::info("🔍 Iniciando limpieza de transacciones duplicadas para liga: {$league->name}", ['league_id' => $league->id]);

        try {
            $leagueUserIds = $league->biwengerUsers->pluck('id')->toArray();
            
            if (empty($leagueUserIds)) {
                Log::info("No hay usuarios en la liga {$league->name}, omitiendo limpieza", ['league_id' => $league->id]);
                return;
            }

            $duplicateGroups = DB::table('transaction')
                ->select('player_id', 'amount', 'from_user_id', 'to_user_id', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
                ->where(function($query) use ($leagueUserIds) {
                    $query->whereIn('from_user_id', $leagueUserIds)
                          ->orWhereIn('to_user_id', $leagueUserIds);
                })
                ->whereNotNull('player_id') // Solo transacciones con jugador
                ->groupBy('player_id', 'amount', 'from_user_id', 'to_user_id')
                ->having('count', '>', 1)
                ->get();

            $totalDuplicatesRemoved = 0;
            $affectedUsers = [];

            foreach ($duplicateGroups as $group) {
                $transactionIds = explode(',', $group->ids);
                $idsToDelete = array_slice($transactionIds, 1);
                
                if (!empty($idsToDelete)) {
                    Log::info("Eliminando transacciones duplicadas", [
                        'league_id' => $league->id,
                        'player_id' => $group->player_id,
                        'amount' => $group->amount,
                        'from_user_id' => $group->from_user_id,
                        'to_user_id' => $group->to_user_id,
                        'total_duplicates' => count($idsToDelete),
                        'keeping_id' => $transactionIds[0],
                        'deleting_ids' => $idsToDelete
                    ]);

                    if ($group->from_user_id) {
                        $affectedUsers[$group->from_user_id] = true;
                    }
                    if ($group->to_user_id) {
                        $affectedUsers[$group->to_user_id] = true;
                    }

                    $deletedCount = Transaction::whereIn('id', $idsToDelete)->delete();
                    $totalDuplicatesRemoved += $deletedCount;
                }
            }

            Log::info("🧹 Limpieza de duplicados completada para liga: {$league->name}", [
                'league_id' => $league->id,
                'grupos_duplicados_encontrados' => count($duplicateGroups),
                'transacciones_eliminadas' => $totalDuplicatesRemoved,
                'usuarios_afectados' => count($affectedUsers)
            ]);

            if ($totalDuplicatesRemoved > 0 && !empty($affectedUsers)) {
                $this->updateBalancesForAffectedUsers($league, array_keys($affectedUsers));
            }

        } catch (\Exception $e) {
            Log::error("❌ Error durante limpieza de duplicados para liga {$league->name}: " . $e->getMessage(), [
                'league_id' => $league->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Update balances for affected users after duplicate transaction removal
     */
    private function updateBalancesForAffectedUsers(League $league, array $userIds): void
    {
        Log::info("💰 Actualizando saldos de usuarios afectados en liga: {$league->name}", [
            'league_id' => $league->id,
            'user_ids' => $userIds
        ]);

        try {
            $biwengerApiService = app(\App\Contracts\BiwengerApiInterface::class);
            $biwengerUsers = $biwengerApiService->getUsers($league);

            $updatedCount = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $user = BiwengerUser::find($userId);
                    if (!$user) {
                        Log::warning("Usuario no encontrado para actualizar saldo", ['user_id' => $userId]);
                        continue;
                    }

                    BiwengerUserBalance::updateBalance($user, $biwengerUsers);
                    $updatedCount++;

                    Log::info("✅ Saldo actualizado para usuario: {$user->name}", [
                        'user_id' => $userId,
                        'league_id' => $league->id
                    ]);

                } catch (\Exception $e) {
                    Log::error("❌ Error actualizando saldo del usuario {$userId}: " . $e->getMessage(), [
                        'user_id' => $userId,
                        'league_id' => $league->id,
                        'exception' => $e->getMessage()
                    ]);
                }
            }

            Log::info("💰 Actualización de saldos completada para liga: {$league->name}", [
                'league_id' => $league->id,
                'usuarios_procesados' => count($userIds),
                'saldos_actualizados' => $updatedCount
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error durante actualización de saldos para liga {$league->name}: " . $e->getMessage(), [
                'league_id' => $league->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
