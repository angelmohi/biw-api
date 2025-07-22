<?php

namespace App\Http\Controllers;

use App\Contracts\BiwengerApiInterface;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    private BiwengerApiInterface $biwengerApi;

    public function __construct(BiwengerApiInterface $biwengerApi)
    {
        $this->biwengerApi = $biwengerApi;
    }
    /**
     * Show list of leagues
     */
    public function index() : View
    {
        $leagues = League::with(['biwengerUsers' => function($query) {
            $query->orderBy('position', 'asc');
        }])->get();

        return view('leagues.index', compact('leagues'));
    }

    /**
     * Show the form for creating a new league.
     */
    public function create() : View
    {
        return view('leagues.create');
    }

    /**
     * Show the specified league with detailed information
     */
    public function show(League $league) : View
    {
        $league->load(['biwengerUsers' => function($query) {
            $query->orderBy('position', 'asc')
                  ->with('balances');
        }]);

        return view('leagues.show', compact('league'));
    }

    /**
     * Store the leagues in the database
     */
    public function store(Request $request) : JsonResponse 
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'biwenger_id' => 'required|integer|unique:league,biwenger_id',
            'bearer_user' => 'required',
            'bearer_league' => 'required',
            'bearer_token' => 'required',
        ]);

        $league = League::create($data);

        $users = $this->biwengerApi->getUsers($league);
        foreach ($users as $user) {
            $league->biwengerUsers()->create([
                'biwenger_id' => $user['biwenger_id'],
                'name' => $user['name'],
                'icon' => $user['icon'],
                'position' => $user['position'],
                'points' => $user['points'],
                'initial_balance' => config('leagues.initial_balance') - $user['teamValue'],
            ]);
        }

        // Refresh the league's statistics
        $league->refreshStatistics();

        flashSuccessMessage('Liga creada correctamente');
        return jsonIframeRedirection(route('leagues.show', $league->id));
    }

    /**
     * Update the league statistics by refreshing data from Biwenger API
     */
    public function update(League $league): JsonResponse
    {
        try {
            // Refresh the league's statistics
            $league->refreshStatistics();
            $league->touch();

            flashSuccessMessage('Liga actualizada correctamente');
            return jsonIframeRedirection(route('leagues.show', $league->id));
        } catch (\Exception $e) {
            flashDangerMessage('Error al actualizar la liga: ' . $e->getMessage());
            return jsonIframeRedirection(route('leagues.show', $league->id));
        }
    }
}
