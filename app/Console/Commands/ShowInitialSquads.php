<?php

namespace App\Console\Commands;

use App\Contracts\BiwengerApiInterface;
use App\Models\League;
use Illuminate\Console\Command;

class ShowInitialSquads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'league:show-initial-squads {league_id : ID de la liga}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Muestra los jugadores que tenía cada usuario el día de inicio de la liga';

    /**
     * The BiwengerApiService instance
     */
    protected BiwengerApiInterface $biwengerApi;

    /**
     * Create a new command instance.
     */
    public function __construct(BiwengerApiInterface $biwengerApi)
    {
        parent::__construct();
        $this->biwengerApi = $biwengerApi;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $leagueId = $this->argument('league_id');
        
        $league = League::find($leagueId);
        
        if (!$league) {
            $this->error("❌ Liga no encontrada con ID: {$leagueId}");
            return 1;
        }

        if (!$league->start_date) {
            $this->error("❌ La liga no tiene fecha de inicio definida");
            return 1;
        }

        $this->info("🏆 Liga: {$league->name}");
        $this->info("📅 Fecha de inicio: {$league->start_date->format('Y-m-d H:i:s')}");
        $this->newLine();

        $startTimestamp = $league->start_date->timestamp;
        $draftToleranceDays = 2;
        $draftWindowEnd = $startTimestamp + ($draftToleranceDays * 86400);

        $this->info("📆 Considerando transacciones hasta: " . date('Y-m-d H:i:s', $draftWindowEnd));
        $this->newLine();

        // Get all players names for the league
        $allPlayers = $this->biwengerApi->getPlayers($league);

        foreach ($league->biwengerUsers as $user) {
            $this->info("👤 Usuario: {$user->name} (ID: {$user->biwenger_id})");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            // Get ALL purchases for this user (grouped by player)
            $purchasesByPlayer = \App\Models\Transaction::where('to_user_id', $user->id)
                ->whereIn('type_id', [1, 2]) // transfer and market
                ->whereNotNull('player_id')
                ->orderBy('date', 'asc')
                ->get()
                ->groupBy('player_id');

            // Get ALL sales for this user (grouped by player)
            $salesByPlayer = \App\Models\Transaction::where('from_user_id', $user->id)
                ->whereIn('type_id', [1, 2]) // transfer and market
                ->whereNotNull('player_id')
                ->orderBy('date', 'asc')
                ->get()
                ->groupBy('player_id');

            // Get current squad from API
            $currentSquad = $this->biwengerApi->getUserSquad($league, $user->biwenger_id);
            $currentPlayerIds = array_column($currentSquad, 'id');

            $initialSquad = [];
            $allPlayerIds = collect($purchasesByPlayer->keys())
                ->merge($salesByPlayer->keys())
                ->merge($currentPlayerIds)
                ->unique()
                ->values();
            
            // Check each player that has interacted with this user
            foreach ($allPlayerIds as $playerId) {
                $purchases = $purchasesByPlayer->get($playerId, collect());
                $sales = $salesByPlayer->get($playerId, collect());
                
                $firstPurchase = $purchases->first();
                $firstSale = $sales->first();
                $isInCurrentSquad = in_array($playerId, $currentPlayerIds);
                
                $hadFromDraft = false;
                $status = '';
                $extraInfo = [];
                
                // Case 1: Never purchased
                if ($purchases->isEmpty()) {
                    // If sold or currently has it, then had it from draft
                    if (!$sales->isEmpty() || $isInCurrentSquad) {
                        $hadFromDraft = true;
                        
                        if (!$sales->isEmpty()) {
                            $status = 'vendido (nunca comprado)';
                            $extraInfo = [
                                'sale_date' => $firstSale->date->format('Y-m-d H:i:s'),
                                'sale_price' => $firstSale->amount,
                            ];
                        } else {
                            $status = 'actualmente en plantilla (nunca comprado)';
                        }
                    }
                }
                // Case 2: First sale is before first purchase
                else if (!$sales->isEmpty() && $firstSale->date < $firstPurchase->date) {
                    $hadFromDraft = true;
                    $status = 'vendido antes de primera compra';
                    $extraInfo = [
                        'first_sale' => $firstSale->date->format('Y-m-d H:i:s'),
                        'first_purchase' => $firstPurchase->date->format('Y-m-d H:i:s'),
                        'sale_price' => $firstSale->amount,
                    ];
                }
                
                if ($hadFromDraft) {
                    $playerName = $allPlayers[$playerId] ?? "Jugador ID: {$playerId}";
                    
                    $initialSquad[$playerId] = [
                        'id' => $playerId,
                        'name' => $playerName,
                        'status' => $status,
                        'extra_info' => $extraInfo,
                    ];
                }
            }

            // Show initial squad
            if (!empty($initialSquad)) {
                $this->info("🟢 Jugadores que tenía al inicio de la liga (del draft):");
                $headers = ['ID', 'Nombre', 'Estado', 'Detalles'];
                $rows = array_map(function($p) {
                    $details = '';
                    if (!empty($p['extra_info'])) {
                        $info = $p['extra_info'];
                        if (isset($info['sale_date'])) {
                            $details = 'Vendido: ' . $info['sale_date'] . ' por ' . number_format($info['sale_price'], 0, ',', '.') . '€';
                        }
                        if (isset($info['first_sale']) && isset($info['first_purchase'])) {
                            $details = 'Venta: ' . $info['first_sale'] . ' / Compra: ' . $info['first_purchase'];
                        }
                    }
                    return [
                        $p['id'],
                        $p['name'],
                        $p['status'],
                        $details
                    ];
                }, array_values($initialSquad));
                $this->table($headers, $rows);
                
                $this->info("📊 Total jugadores iniciales: " . count($initialSquad));
                
                // Calculate initial team value
                $totalInitialValue = 0;
                $playersWithPrice = 0;
                $playersWithoutPrice = 0;
                $missingPlayers = [];
                
                // Convert start_date to YYMMDD format for API lookup
                $dateKey = (int) $league->start_date->format('ymd');
                
                foreach ($initialSquad as $player) {
                    $price = null;
                    
                    // First try: local database
                    $priceRecord = \App\Models\PlayerPriceHistory::where('biwenger_player_id', $player['id'])
                        ->where('record_date', $league->start_date->format('Y-m-d'))
                        ->first();
                    
                    if ($priceRecord) {
                        $price = $priceRecord->price;
                    } else {
                        // Second try: fetch from Biwenger API
                        $this->warn("   ⚠️  Obteniendo precio de {$player['name']} desde API...");
                        $prices = $this->biwengerApi->getPlayerPrices($league, $player['id']);
                        
                        if (isset($prices[$dateKey])) {
                            $price = $prices[$dateKey];
                            $this->info("   ✓ Precio encontrado: " . number_format($price, 0, ',', '.') . '€');
                        }
                    }
                    
                    if ($price) {
                        $totalInitialValue += $price;
                        $playersWithPrice++;
                    } else {
                        $playersWithoutPrice++;
                        $missingPlayers[] = $player['name'] . ' (ID: ' . $player['id'] . ')';
                    }
                }
                
                $this->newLine();
                $this->info("📈 Valor de mercado inicial (día {$league->start_date->format('Y-m-d')}): " . number_format($totalInitialValue, 0, ',', '.') . '€');
                $this->info("   └─ Jugadores con precio histórico: {$playersWithPrice}");
                
                if ($playersWithoutPrice > 0) {
                    $this->warn("   └─ Jugadores SIN precio histórico: {$playersWithoutPrice}");
                    foreach ($missingPlayers as $missingPlayer) {
                        $this->warn("      • {$missingPlayer}");
                    }
                }
                
                $initialBalance = 40000000 - $totalInitialValue;
                $this->info("💵 Balance inicial calculado: " . number_format($initialBalance, 0, ',', '.') . '€');
            } else {
                $this->warn("⚠️  No se identificaron jugadores del draft inicial");
                $this->warn("   Esto puede significar que todos los jugadores actuales fueron comprados después");
            }

            $this->newLine(2);
        }

        $this->info("✅ Proceso completado");
        return 0;
    }
}
