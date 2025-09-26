<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\League;
use App\Models\PlayerPriceHistory;
use App\Services\BiwengerApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePlayerPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'biwenger:update-player-prices 
                            {--league-id= : ID específico de liga para procesar}
                            {--force : Forzar actualización aunque ya existan registros para la fecha}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene todos los jugadores de Biwenger con sus precios e incrementos diarios y los almacena para seguimiento histórico';

    /**
     * The BiwengerApiService instance
     */
    protected BiwengerApiService $biwengerService;

    /**
     * Create a new command instance.
     */
    public function __construct(BiwengerApiService $biwengerService)
    {
        parent::__construct();
        $this->biwengerService = $biwengerService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando actualización de precios de jugadores...');
        
        $leagueId = $this->option('league-id');
        $date = Carbon::today();
        $force = $this->option('force');

        try {
            $leagues = $this->getLeaguesToProcess($leagueId);
            
            if ($leagues->isEmpty()) {
                $this->error('❌ No se encontraron ligas para procesar.');
                return 1;
            }

            $this->info("📊 Procesando {$leagues->count()} liga(s) para la fecha: {$date->format('Y-m-d')}");

            $totalPlayersUpdated = 0;
            $totalErrors = 0;

            foreach ($leagues as $league) {
                $this->line("🏆 Procesando liga: {$league->name} (ID: {$league->id})");

                try {
                    $result = $this->updatePlayerPricesForLeague($league, $date, $force);
                    $totalPlayersUpdated += $result['updated'];
                    $totalErrors += $result['errors'];

                    $this->info("✅ Liga procesada - Jugadores: {$result['updated']}, Errores: {$result['errors']}");

                } catch (\Exception $e) {
                    $this->error("❌ Error procesando liga {$league->id}: " . $e->getMessage());
                    Log::error('Error updating player prices for league', [
                        'league_id' => $league->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $totalErrors++;
                }
            }

            $this->newLine();
            $this->info("🎉 Proceso completado:");
            $this->info("   • Total de jugadores actualizados: {$totalPlayersUpdated}");
            $this->info("   • Total de errores: {$totalErrors}");
            $this->info("   • Fecha de registro: {$date->format('Y-m-d')}");

            return $totalErrors > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('❌ Error crítico en el comando: ' . $e->getMessage());
            Log::error('Critical error in UpdatePlayerPrices command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Get leagues to process based on options
     */
    protected function getLeaguesToProcess($leagueId): \Illuminate\Database\Eloquent\Collection
    {
        $query = League::query();

        if ($leagueId) {
            $query->where('id', $leagueId);
        }

        $query->whereNotNull('bearer_token')
              ->whereNotNull('bearer_league')
              ->whereNotNull('bearer_user')
              ->whereNotNull('biwenger_id');

        return $query->get();
    }

    /**
     * Update player prices for a specific league
     */
    protected function updatePlayerPricesForLeague(League $league, Carbon $date, bool $force): array
    {
        if (!$force) {
            $existingCount = PlayerPriceHistory::forDate($date)->count();
            if ($existingCount > 0) {
                $this->warn("⚠️  Ya existen {$existingCount} registros para la fecha {$date->format('Y-m-d')}. Use --force para sobrescribir.");
                return ['updated' => 0, 'errors' => 0];
            }
        }

        $playersData = $this->biwengerService->getPlayersDetailedData($league);

        if (empty($playersData)) {
            $this->warn('⚠️  No se obtuvieron datos de jugadores de la API de Biwenger.');
            return ['updated' => 0, 'errors' => 1];
        }

        $this->info("📥 Obtenidos " . count($playersData) . " jugadores de Biwenger API");

        $updated = 0;
        $errors = 0;
        $progressBar = $this->output->createProgressBar(count($playersData));

        DB::beginTransaction();
        
        try {
            foreach ($playersData as $playerData) {
                try {
                    $playerData['record_date'] = $date;

                    if ($force) {
                        PlayerPriceHistory::where('biwenger_player_id', $playerData['biwenger_player_id'])
                                         ->where('record_date', $date)
                                         ->delete();
                    }

                    PlayerPriceHistory::updateOrCreate(
                        [
                            'biwenger_player_id' => $playerData['biwenger_player_id'],
                            'record_date' => $date
                        ],
                        $playerData
                    );

                    $updated++;
                    
                } catch (\Exception $e) {
                    $this->error("Error procesando jugador {$playerData['player_name']}: " . $e->getMessage());
                    Log::error('Error updating individual player price', [
                        'player_id' => $playerData['biwenger_player_id'],
                        'player_name' => $playerData['player_name'],
                        'error' => $e->getMessage()
                    ]);
                    $errors++;
                }

                $progressBar->advance();
            }

            DB::commit();
            $progressBar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $progressBar->finish();
            $this->newLine();
            throw $e;
        }

        return [
            'updated' => $updated,
            'errors' => $errors
        ];
    }
}
