<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\League;
use App\Models\PlayerPriceHistory;
use App\Services\BiwengerApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPlayerPriceHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'biwenger:import-player-history 
                            {--league-id= : ID específico de liga para procesar}
                            {--player-slug= : Slug específico de jugador para importar}
                            {--days= : Número de días hacia atrás para importar (por defecto: todos disponibles)}
                            {--force : Sobrescribir registros existentes}
                            {--delay=500 : Delay en milisegundos entre llamadas API}
                            {--batch-size=10 : Número de jugadores a procesar por lote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa el historial completo de precios de jugadores desde la API de Biwenger usando los slugs';

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
        $this->info('🔄 Iniciando importación de historial de precios de jugadores...');
        
        // Parse opciones
        $leagueId = $this->option('league-id');
        $playerSlug = $this->option('player-slug');
        $days = $this->option('days') ? (int)$this->option('days') : null;
        $force = $this->option('force');
        $delay = (int)$this->option('delay');
        $batchSize = (int)$this->option('batch-size');

        try {
            $leagues = $this->getLeaguesToProcess($leagueId);
            
            if ($leagues->isEmpty()) {
                $this->error('❌ No se encontraron ligas para procesar.');
                return 1;
            }

            $this->info("📊 Procesando {$leagues->count()} liga(s)");

            $totalPlayersProcessed = 0;
            $totalRecordsImported = 0;
            $totalErrors = 0;

            foreach ($leagues as $league) {
                $this->line("🏆 Procesando liga: {$league->name} (ID: {$league->id})");

                try {
                    $result = $this->importHistoryForLeague($league, $playerSlug, $days, $force, $delay, $batchSize);
                    $totalPlayersProcessed += $result['players_processed'];
                    $totalRecordsImported += $result['records_imported'];
                    $totalErrors += $result['errors'];

                    $this->info("✅ Liga procesada - Jugadores: {$result['players_processed']}, Registros: {$result['records_imported']}, Errores: {$result['errors']}");

                } catch (\Exception $e) {
                    $this->error("❌ Error procesando liga {$league->id}: " . $e->getMessage());
                    Log::error('Error importing player price history for league', [
                        'league_id' => $league->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $totalErrors++;
                }
            }

            $this->newLine();
            $this->info("🎉 Proceso completado:");
            $this->info("   • Total de jugadores procesados: {$totalPlayersProcessed}");
            $this->info("   • Total de registros importados: {$totalRecordsImported}");
            $this->info("   • Total de errores: {$totalErrors}");

            return $totalErrors > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('❌ Error crítico en el comando: ' . $e->getMessage());
            Log::error('Critical error in ImportPlayerPriceHistory command', [
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
     * Import price history for a specific league
     */
    protected function importHistoryForLeague(League $league, ?string $specificPlayerSlug, ?int $days, bool $force, int $delay, int $batchSize): array
    {
        $playersProcessed = 0;
        $recordsImported = 0;
        $errors = 0;

        if ($specificPlayerSlug) {
            $playersToProcess = [['slug' => $specificPlayerSlug, 'player_name' => 'Jugador específico']];
            $this->info("🎯 Procesando jugador específico: {$specificPlayerSlug}");
        } else {
            $this->info("📥 Obteniendo lista de todos los jugadores con slugs válidos...");
            $playersToProcess = $this->biwengerService->getPlayerSlugs($league);
            
            if (empty($playersToProcess)) {
                $this->warn('⚠️  No se obtuvieron jugadores con slugs válidos de la API de Biwenger.');
                return ['players_processed' => 0, 'records_imported' => 0, 'errors' => 1];
            }

            $this->info("📊 Resumen de jugadores:");
            $this->line("   • Jugadores con slug válido encontrados: " . count($playersToProcess));
            $this->line("   • Listos para importar historial completo");
        }

        if (empty($playersToProcess)) {
            $this->warn('⚠️  No hay jugadores con slug válido para procesar.');
            return ['players_processed' => 0, 'records_imported' => 0, 'errors' => 0];
        }

        $batches = array_chunk($playersToProcess, $batchSize);
        $totalBatches = count($batches);

        $this->info("📦 Procesando en {$totalBatches} lote(s) de hasta {$batchSize} jugadores");

        foreach ($batches as $batchIndex => $batch) {
            $this->line("📦 Procesando lote " . ($batchIndex + 1) . "/{$totalBatches}");
            
            $progressBar = $this->output->createProgressBar(count($batch));
            $progressBar->setFormat('verbose');
            
            foreach ($batch as $player) {
                try {
                    $progressBar->setMessage("Procesando: {$player['player_name']} ({$player['slug']})", 'status');
                    
                    $result = $this->importPlayerHistory($league, $player['slug'], $days, $force);
                    $recordsImported += $result['records_imported'];
                    $playersProcessed++;
                    
                    if ($result['errors'] > 0) {
                        $errors += $result['errors'];
                    }
                    
                    // Log de progreso cada 10 jugadores
                    if ($playersProcessed % 10 === 0) {
                        $this->line(" ✅ {$playersProcessed} jugadores procesados, {$recordsImported} registros importados");
                    }
                    
                } catch (\Exception $e) {
                    $this->error("Error procesando jugador {$player['player_name']} ({$player['slug']}): " . $e->getMessage());
                    Log::error('Error importing single player history', [
                        'player_slug' => $player['slug'],
                        'player_name' => $player['player_name'],
                        'league_id' => $league->id,
                        'error' => $e->getMessage()
                    ]);
                    $errors++;
                }

                $progressBar->advance();
                
                if ($delay > 0) {
                    usleep($delay * 1000);
                }
            }

            $progressBar->finish();
            $this->newLine();
            
            $this->line("✅ Lote " . ($batchIndex + 1) . " completado:");
            $this->line("   • Jugadores procesados en este lote: " . count($batch));
            $this->line("   • Total acumulado: {$playersProcessed} jugadores, {$recordsImported} registros");
            
            if ($batchIndex < $totalBatches - 1) {
                $this->line("⏸️  Pausa entre lotes (2 segundos)...");
                sleep(2);
            }
        }

        return [
            'players_processed' => $playersProcessed,
            'records_imported' => $recordsImported,
            'errors' => $errors
        ];
    }

    /**
     * Import price history for a single player
     */
    protected function importPlayerHistory(League $league, string $playerSlug, ?int $days, bool $force): array
    {
        $playerData = $this->biwengerService->getPlayerPriceHistory($league, $playerSlug);
        
        if (empty($playerData) || !isset($playerData['price_history'])) {
            return ['records_imported' => 0, 'errors' => 1];
        }

        $priceHistory = $playerData['price_history'];
        
        if ($days !== null) {
            $cutoffDate = Carbon::now()->subDays($days);
            $priceHistory = array_filter($priceHistory, function($record) use ($cutoffDate) {
                return Carbon::parse($record['record_date'])->gte($cutoffDate);
            });
        }

        if (empty($priceHistory)) {
            return ['records_imported' => 0, 'errors' => 0];
        }

        $recordsImported = 0;
        $errors = 0;

        DB::beginTransaction();
        
        try {
            foreach ($priceHistory as $priceRecord) {
                try {
                    $exists = PlayerPriceHistory::where('biwenger_player_id', $priceRecord['biwenger_player_id'])
                        ->where('record_date', $priceRecord['record_date'])
                        ->exists();

                    if ($exists && !$force) {
                        continue;
                    }

                    if ($exists && $force) {
                        PlayerPriceHistory::where('biwenger_player_id', $priceRecord['biwenger_player_id'])
                            ->where('record_date', $priceRecord['record_date'])
                            ->delete();
                    }

                    PlayerPriceHistory::create($priceRecord);
                    $recordsImported++;
                    
                } catch (\Exception $e) {
                    Log::error('Error importing individual price record', [
                        'player_slug' => $playerSlug,
                        'record_date' => $priceRecord['record_date'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    $errors++;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'records_imported' => $recordsImported,
            'errors' => $errors
        ];
    }
}
