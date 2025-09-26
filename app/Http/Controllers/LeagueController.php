<?php

namespace App\Http\Controllers;

use App\Contracts\BiwengerApiInterface;
use App\Models\BiwengerUser;
use App\Models\League;
use App\Models\BiwengerUserBalance;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeagueController extends Controller
{
    private BiwengerApiInterface $biwengerApi;

    public function __construct(BiwengerApiInterface $biwengerApi)
    {
        $this->middleware('auth');
        $this->biwengerApi = $biwengerApi;
    }
    /**
     * Show list of leagues
     */
    public function index() : View
    {
        $user = Auth::user();
        
        // Full Administrators can see all leagues
        if ($user->isFullAdministrator()) {
            $leagues = League::with(['biwengerUsers' => function($query) {
                $query->orderBy('position', 'asc');
            }])->get();
        } else {
            // Get only leagues that the user has access to
            $leagues = League::whereHas('users', function($query) use ($user) {
                $query->where('users.id', $user->id)
                      ->where('user_leagues.is_active', true);
            })->with(['biwengerUsers' => function($query) {
                $query->orderBy('position', 'asc');
            }])->get();
        }

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
        $user = Auth::user();
        
        // Check if user has access to this league
        if (!$league->hasUser($user->id)) {
            abort(403, 'No tienes acceso a esta liga.');
        }

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
        $user = Auth::user();
        
        // Check if user has access to this league
        if (!$league->hasUser($user->id)) {
            return response()->json(['error' => 'No tienes acceso a esta liga.'], 403);
        }

        // Check if this is a mobile request
        $isMobile = $request->has('mobile') && $request->get('mobile');
        
        if ($isMobile) {
            return $this->getMobileTransactions($request, $league);
        }

        // Get DataTables parameters directly from request
        $draw = intval($request->get('draw', 1));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 10));
        $searchValue = '';
        
        // Extract search value from DataTables format
        if ($request->has('search') && is_array($request->get('search'))) {
            $searchArray = $request->get('search');
            $searchValue = isset($searchArray['value']) ? trim($searchArray['value']) : '';
        }
        
        // Extract order parameters
        $orderColumn = 5; // Default to date column (now column 5 instead of 6)
        $orderDirection = 'desc'; // Default to descending
        if ($request->has('order') && is_array($request->get('order'))) {
            $orderArray = $request->get('order');
            if (isset($orderArray[0]) && is_array($orderArray[0])) {
                $orderColumn = intval($orderArray[0]['column'] ?? 5);
                $orderDirection = ($orderArray[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            }
        }
        
        Log::info("DataTables Raw Parameters", [
            'draw' => $draw,
            'start' => $start, 
            'length' => $length,
            'search_value' => $searchValue,
            'order_column' => $orderColumn,
            'order_direction' => $orderDirection
        ]);
        
        // Get all transactions for this league
        $allTransactions = $league->transactions()
            ->with(['userFrom', 'userTo', 'type'])
            ->orderBy('date', 'desc')
            ->get();
            
        $totalRecords = $allTransactions->count();
        
        // Filter transactions based on search
        $filteredTransactions = $allTransactions;
        
        if (!empty($searchValue)) {
            $filteredTransactions = $allTransactions->filter(function($transaction) use ($searchValue) {
                // Check description
                if ($transaction->description && stripos($transaction->description, $searchValue) !== false) {
                    return true;
                }
                
                // Check player name
                if ($transaction->player_name && stripos($transaction->player_name, $searchValue) !== false) {
                    return true;
                }
                
                // Check amount (both raw and formatted)
                if ($transaction->amount) {
                    $formattedAmount = number_format($transaction->amount, 0, ',', '.');
                    if (stripos((string)$transaction->amount, $searchValue) !== false || 
                        stripos($formattedAmount, $searchValue) !== false) {
                        return true;
                    }
                }
                
                // Check transaction type
                $typeText = '';
                if ($transaction->type && $transaction->type->name) {
                    $typeText = $transaction->type->name;
                } else {
                    $typeText = match($transaction->type_id) {
                        1 => 'Traspaso',
                        2 => 'Mercado', 
                        3 => 'Jornada',
                        4 => 'Cláusula',
                        default => 'Desconocido'
                    };
                }
                if (stripos($typeText, $searchValue) !== false) {
                    return true;
                }
                
                // Check user from
                if ($transaction->userFrom && stripos($transaction->userFrom->name, $searchValue) !== false) {
                    return true;
                }
                
                // Check user to
                if ($transaction->userTo && stripos($transaction->userTo->name, $searchValue) !== false) {
                    return true;
                }
                
                // Check date formats
                if ($transaction->date) {
                    $dateShort = $transaction->date->format('d/m/Y');
                    $dateLong = $transaction->date->format('d/m/Y H:i');
                    if (stripos($dateShort, $searchValue) !== false || stripos($dateLong, $searchValue) !== false) {
                        return true;
                    }
                }
                
                return false;
            });
        }
        
        $filteredRecords = $filteredTransactions->count();
        
        // Apply sorting
        $sortedTransactions = $filteredTransactions->sort(function($a, $b) use ($orderColumn, $orderDirection) {
            $valueA = null;
            $valueB = null;
            
            switch ($orderColumn) {
                case 0: // Type (including description)
                    $valueA = $a->type_id ?? 0;
                    $valueB = $b->type_id ?? 0;
                    // If types are the same, sort by description
                    if ($valueA === $valueB) {
                        $valueA = $a->description ?? '';
                        $valueB = $b->description ?? '';
                    }
                    break;
                case 1: // Amount
                    $valueA = $a->amount ?? 0;
                    $valueB = $b->amount ?? 0;
                    break;
                case 2: // Player name
                    $valueA = $a->player_name ?? '';
                    $valueB = $b->player_name ?? '';
                    break;
                case 3: // User from
                    $valueA = $a->userFrom ? $a->userFrom->name : 'zzz';
                    $valueB = $b->userFrom ? $b->userFrom->name : 'zzz';
                    break;
                case 4: // User to
                    $valueA = $a->userTo ? $a->userTo->name : 'zzz';
                    $valueB = $b->userTo ? $b->userTo->name : 'zzz';
                    break;
                case 5: // Date
                default:
                    $valueA = $a->date ? $a->date->timestamp : 0;
                    $valueB = $b->date ? $b->date->timestamp : 0;
                    break;
            }
            
            // Compare values
            if (is_numeric($valueA) && is_numeric($valueB)) {
                $result = $valueA <=> $valueB;
            } else {
                $result = strcasecmp((string)$valueA, (string)$valueB);
            }
            
            // Apply direction
            return $orderDirection === 'desc' ? -$result : $result;
        });
        
        // Reset collection keys after sorting
        $sortedTransactions = $sortedTransactions->values();
        
        // Apply pagination
        $paginatedTransactions = $sortedTransactions->slice($start, $length);
        
        // Format data for DataTables
        $data = [];
        foreach ($paginatedTransactions as $transaction) {
            // Type badge - Combine type and description within parentheses
            $typeText = match($transaction->type_id) {
                1 => 'Traspaso',
                2 => 'Mercado',
                3 => 'Jornada',
                4 => 'Cláusula',
                default => 'Tipo ' . $transaction->type_id
            };
            
            // Add description within parentheses if not empty
            if (!empty($transaction->description)) {
                $typeText .= ' (' . $transaction->description . ')';
            }
            
            $typeBadge = match($transaction->type_id) {
                1 => '<span class="badge bg-primary"><i class="fas fa-exchange-alt me-1"></i>' . e($typeText) . '</span>',
                2 => '<span class="badge bg-success"><i class="fas fa-shopping-cart me-1"></i>' . e($typeText) . '</span>',
                3 => '<span class="badge bg-info"><i class="fas fa-clock me-1"></i>' . e($typeText) . '</span>',
                4 => '<span class="badge bg-warning text-dark"><i class="fas fa-arrow-up me-1"></i>' . e($typeText) . '</span>',
                default => '<span class="badge bg-secondary">' . e($typeText) . '</span>'
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
                $amount,
                e($transaction->player_name ?? '-'),
                $userFrom,
                $userTo,
                '<small>' . $transaction->date->format('d/m/Y H:i') . '</small>'
            ];
        }
        
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
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

            // Cleanup duplicate transactions and update affected user balances
            $this->cleanupDuplicateTransactionsForLeague($league);

            flashSuccessMessage('Liga actualizada correctamente');
            return jsonIframeRedirection(route('leagues.show', $league->id));
        } catch (\Exception $e) {
            flashDangerMessage('Error al actualizar la liga: ' . $e->getMessage());
            return jsonIframeRedirection(route('leagues.show', $league->id));
        }
    }

    /**
     * Get balance history data for chart
     */
    public function getBalanceChart(Request $request, League $league)
    {
        try {
            $user = Auth::user();
            
            // Check if user has access to this league
            if (!$league->hasUser($user->id)) {
                Log::error('User does not have access to league', ['user_id' => $user->id, 'league_id' => $league->id]);
                return response()->json(['error' => 'No tienes acceso a esta liga.'], 403);
            }

            Log::info('Getting balance chart for league', ['league_id' => $league->id, 'league_name' => $league->name]);

            // Get all users in the league
            $users = $league->biwengerUsers()->orderBy('name')->get();
            Log::info('Found users in league', ['user_count' => $users->count()]);
            
            if ($users->isEmpty()) {
                Log::warning('No users found in league', ['league_id' => $league->id]);
                return response()->json([
                    'labels' => [],
                    'datasets' => []
                ]);
            }
            
            // Get all unique dates from balance records for this league
            $allDates = BiwengerUserBalance::whereIn('user_id', $users->pluck('id'))
                ->selectRaw('DATE(created_at) as date')
                ->distinct()
                ->orderBy('date')
                ->pluck('date')
                ->toArray();
            
            Log::info('Found balance dates', ['date_count' => count($allDates), 'first_date' => $allDates[0] ?? null, 'last_date' => end($allDates) ?: null]);
            
            if (empty($allDates)) {
                Log::warning('No balance data found for league users', ['league_id' => $league->id, 'user_ids' => $users->pluck('id')->toArray()]);
                return response()->json([
                    'labels' => [],
                    'datasets' => []
                ]);
            }
            
            // Filter dates to show only every 3 days, but always include first and last
            $filteredDates = [];
            $totalDates = count($allDates);
            
            for ($i = 0; $i < $totalDates; $i++) {
                // Include first date, every 3rd date, and last date
                if ($i === 0 || $i % 3 === 0 || $i === $totalDates - 1) {
                    $filteredDates[] = $allDates[$i];
                }
            }
            
            Log::info('Filtered dates for chart', ['original_count' => $totalDates, 'filtered_count' => count($filteredDates)]);
            
            // Get balance history for all users
            $chartData = [
                'labels' => [],
                'datasets' => []
            ];
            
            // Format dates for display
            $chartData['labels'] = array_map(function($date) {
                return \Carbon\Carbon::parse($date)->format('d/m/Y');
            }, $filteredDates);
            
            $colors = [
                'rgb(228, 26, 28)',
                'rgb(55, 126, 184)',
                'rgb(77, 175, 74)',
                'rgb(152, 78, 163)',
                'rgb(255, 127, 0)',
                'rgb(166, 86, 40)',
                'rgb(247, 129, 191)',
                'rgb(153, 153, 153)',
                'rgb(51, 160, 44)',
                'rgb(31, 120, 180)',
                'rgb(227, 26, 28)',
                'rgb(106, 61, 154)',
                'rgb(255, 191, 0)',
                'rgb(202, 178, 214)',
                'rgb(253, 180, 98)',
                'rgb(178, 223, 138)',
                'rgb(166, 206, 227)',
                'rgb(251, 128, 114)',
                'rgb(128, 177, 211)',
                'rgb(255, 255, 51)'
            ];
            
            $colorIndex = 0;
            
            foreach ($users as $user) {
                $balanceData = [];
                
                foreach ($filteredDates as $date) {
                    $balance = BiwengerUserBalance::where('user_id', $user->id)
                        ->whereDate('created_at', $date)
                        ->first();
                    
                    $balanceData[] = $balance ? (float)$balance->balance : null;
                }
                
                $userIcon = $user->icon;
                Log::info('User icon data', [
                    'user_name' => $user->name,
                    'user_id' => $user->id,
                    'icon' => $userIcon,
                    'icon_length' => strlen($userIcon ?? ''),
                    'icon_starts_with_http' => str_starts_with($userIcon ?? '', 'http')
                ]);

                $chartData['datasets'][] = [
                    'label' => $user->name,
                    'data' => $balanceData,
                    'borderColor' => $colors[$colorIndex % count($colors)],
                    'backgroundColor' => str_replace('rgb', 'rgba', str_replace(')', ', 0.1)', $colors[$colorIndex % count($colors)])),
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 8,
                    'spanGaps' => true,
                    'userIcon' => $userIcon,
                    'userId' => $user->id
                ];
                
                $colorIndex++;
            }
            
            Log::info('Returning chart data', ['labels_count' => count($chartData['labels']), 'datasets_count' => count($chartData['datasets'])]);
            return response()->json($chartData);
            
        } catch (\Exception $e) {
            Log::error('Error in getBalanceChart', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Get transactions data for mobile view
     */
    private function getMobileTransactions(Request $request, League $league)
    {
        $length = $request->get('length', 20); // Default 20 for mobile
        
        // Get transactions without search functionality
        $query = $league->transactions();
        $totalRecords = $query->count();
        
        // Apply ordering and limit
        $transactions = $query->orderBy('date', 'desc')->limit($length)->get();
        
        // Format data for mobile cards
        $data = [];
        foreach ($transactions as $transaction) {
            // Type badge (simplified for mobile) - Include description within parentheses if not empty
            $typeText = match($transaction->type_id) {
                1 => 'Traspaso',
                2 => 'Mercado',
                3 => 'Jornada',
                4 => 'Cláusula',
                default => 'Desconocido'
            };
            
            // Add description within parentheses if not empty
            if (!empty($transaction->description)) {
                $typeText .= ' (' . $transaction->description . ')';
            }
            
            $typeBadge = match($transaction->type_id) {
                1 => '<span class="badge bg-primary">' . e($typeText) . '</span>',
                2 => '<span class="badge bg-success">' . e($typeText) . '</span>',
                3 => '<span class="badge bg-info">' . e($typeText) . '</span>',
                4 => '<span class="badge bg-warning text-dark">' . e($typeText) . '</span>',
                default => '<span class="badge bg-secondary">' . e($typeText) . '</span>'
            };
            
            // Amount formatting
            $amount = $transaction->amount 
                ? '<span class="fw-bold text-success">' . number_format($transaction->amount, 0, ',', '.') . '€</span>'
                : '<span class="text-muted">-</span>';
            
            // User from
            $userFrom = $transaction->userFrom 
                ? $transaction->userFrom->name
                : 'Mercado';
            
            // User to
            $userTo = $transaction->userTo 
                ? $transaction->userTo->name
                : 'Mercado';
            
            $data[] = [
                $typeBadge,
                $amount,
                $transaction->player_name ?? '',
                $userFrom,
                $userTo,
                $transaction->date->format('d/m/Y H:i')
            ];
        }
        
        return response()->json([
            'data' => $data,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords // Same as total since no filtering
        ]);
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
