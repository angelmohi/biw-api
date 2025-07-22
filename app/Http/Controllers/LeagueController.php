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
     * Get transactions data for DataTables
     */
    public function getTransactions(Request $request, League $league)
    {
        $dataTablesRequest = new \App\Helpers\DataTablesRequest($request);
        
        // Base query for transactions
        $baseQuery = $league->transactions();
        
        // Total records count
        $totalRecords = $baseQuery->count();
        
        // Apply search filter if provided
        $query = $league->transactions();
        if ($dataTablesRequest->search()) {
            $searchTerm = $dataTablesRequest->search();
            $query->where(function($q) use ($searchTerm) {
                $q->where('description', 'like', "%{$searchTerm}%")
                  ->orWhere('player_name', 'like', "%{$searchTerm}%")
                  ->orWhere('amount', 'like', "%{$searchTerm}%")
                  ->orWhereHas('userFrom', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('userTo', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }
        
        // Count filtered records
        $filteredRecords = $query->count();
        
        // Apply sorting
        $orderColumns = [
            0 => 'type_id',
            1 => 'description', 
            2 => 'amount',
            3 => 'player_name',
            4 => 'from_user_id',
            5 => 'to_user_id',
            6 => 'date'
        ];
        
        $hasJoins = false;
        foreach ($dataTablesRequest->order() as $columnIndex => $direction) {
            if (isset($orderColumns[$columnIndex])) {
                $column = $orderColumns[$columnIndex];
                
                // Handle special cases for user relationships
                if ($column === 'from_user_id' && !$hasJoins) {
                    $query->leftJoin('biwenger_user as from_user', 'transaction.from_user_id', '=', 'from_user.id')
                          ->select('transaction.*')
                          ->orderBy('from_user.name', $direction);
                    $hasJoins = true;
                } elseif ($column === 'to_user_id' && !$hasJoins) {
                    $query->leftJoin('biwenger_user as to_user', 'transaction.to_user_id', '=', 'to_user.id')
                          ->select('transaction.*')
                          ->orderBy('to_user.name', $direction);
                    $hasJoins = true;
                } else {
                    $query->orderBy('transaction.' . $column, $direction);
                }
            }
        }
        
        // Default sort if no order specified
        if (empty($dataTablesRequest->order())) {
            $query->orderBy('transaction.date', 'desc');
        }
        
        // Apply pagination
        $transactions = $query->offset($dataTablesRequest->start())
                             ->limit($dataTablesRequest->length())
                             ->get();
        
        // Format data for DataTables
        $data = [];
        foreach ($transactions as $transaction) {
            // Type badge
            $typeBadge = match($transaction->type_id) {
                1 => '<span class="badge bg-primary"><i class="fas fa-exchange-alt me-1"></i>Traspaso</span>',
                2 => '<span class="badge bg-success"><i class="fas fa-shopping-cart me-1"></i>Mercado</span>',
                3 => '<span class="badge bg-info"><i class="fas fa-clock me-1"></i>Jornada</span>',
                default => '<span class="badge bg-secondary">Desconocido</span>'
            };
            
            // Amount formatting
            $amount = $transaction->amount 
                ? '<span class="fw-bold">' . number_format($transaction->amount, 0, ',', '.') . ' <small class="text-muted">€</small></span>'
                : '<span class="text-muted">-</span>';
            
            // User from
            $userFrom = $transaction->userFrom 
                ? '<span class="text-danger">' . e($transaction->userFrom->name) . '</span>'
                : '<span class="text-muted">Mercado</span>';
            
            // User to
            $userTo = $transaction->userTo 
                ? '<span class="text-success">' . e($transaction->userTo->name) . '</span>'
                : '<span class="text-muted">Mercado</span>';
            
            $data[] = [
                $typeBadge,
                e($transaction->description ?? '-'),
                $amount,
                e($transaction->player_name ?? '-'),
                $userFrom,
                $userTo,
                '<small>' . $transaction->date->format('d/m/Y H:i') . '</small>'
            ];
        }
        
        return response()->json(\App\Helpers\DataTablesResponse::output(
            $dataTablesRequest,
            $data,
            $totalRecords,
            $filteredRecords
        ));
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
            'start_date' => 'nullable|date',
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
