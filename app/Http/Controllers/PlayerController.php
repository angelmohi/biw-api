<?php

namespace App\Http\Controllers;

use App\Models\PlayerPriceHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    /**
     * Display the main players page
     */
    public function index()
    {
        $today = Carbon::today();
        
        // Get all players with today's data
        $todaysPlayers = PlayerPriceHistory::forDate($today)
            ->orderBy('player_name')
            ->get();

        // Get top 5 price increases
        $topIncreases = PlayerPriceHistory::forDate($today)
            ->whereNotNull('price_increment')
            ->where('price_increment', '>', 0)
            ->orderBy('price_increment', 'desc')
            ->limit(5)
            ->get();

        // Get top 5 price decreases
        $topDecreases = PlayerPriceHistory::forDate($today)
            ->whereNotNull('price_increment')
            ->where('price_increment', '<', 0)
            ->orderBy('price_increment', 'asc')
            ->limit(5)
            ->get();

        return view('players.index', compact('todaysPlayers', 'topIncreases', 'topDecreases', 'today'));
    }

    /**
     * Display individual player page with chart
     */
    public function show($playerId)
    {
        // Get player info from most recent record by date
        $player = PlayerPriceHistory::where('biwenger_player_id', $playerId)
            ->orderBy('record_date', 'desc')
            ->first();

        if (!$player) {
            abort(404, 'Jugador no encontrado');
        }

        // Get recent price history (last 30 days)
        $priceHistory = PlayerPriceHistory::where('biwenger_player_id', $playerId)
            ->where('record_date', '>=', Carbon::now()->subDays(30))
            ->orderBy('record_date', 'asc')
            ->get();

        return view('players.show', compact('player', 'priceHistory'));
    }

    /**
     * Get chart data for a specific player (AJAX endpoint)
     */
    public function getChartData($playerId, Request $request)
    {
        $days = $request->get('days', 30);
        $fromDate = Carbon::now()->subDays($days);

        $priceHistory = PlayerPriceHistory::where('biwenger_player_id', $playerId)
            ->where('record_date', '>=', $fromDate)
            ->orderBy('record_date', 'asc')
            ->get();

        $chartData = [
            'labels' => [],
            'prices' => [],
            'increments' => []
        ];

        foreach ($priceHistory as $record) {
            $chartData['labels'][] = $record->record_date->format('d/m/Y');
            $chartData['prices'][] = $record->getPriceInEuros();
            $chartData['increments'][] = $record->getPriceIncrementInEuros();
        }

        return response()->json($chartData);
    }
}
