<?php

namespace App\Models;

use App\Services\LeagueStatsService;
use Illuminate\Database\Eloquent\SoftDeletes;

class League extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'league';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'position' => 'integer',
        'start_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The users that belong to the league.
     */
    public function biwengerUsers()
    {
        return $this->hasMany(BiwengerUser::class, 'league_id', 'id');
    }

    /**
     * The authenticated users that have access to this league.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_leagues')
                    ->withPivot('is_active')
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * Get all users (including inactive ones) that belong to the league.
     */
    public function allUsers()
    {
        return $this->belongsToMany(User::class, 'user_leagues')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    /**
     * Check if a user has access to this league.
     */
    public function hasUser($userId): bool
    {
        $user = User::find($userId);
        
        // Full Administrators always have access
        if ($user && $user->isFullAdministrator()) {
            return true;
        }

        return $this->users()->where('users.id', $userId)->exists();
    }

    /**
     * Get users with a specific role in this league.
     * Note: This method is deprecated since we removed roles from user_leagues
     */
    public function getUsersByRole($role)
    {
        // This method is no longer relevant since we removed roles
        return collect();
    }

    /**
     * Get the owner(s) of this league.
     * Note: This method is deprecated since we removed roles from user_leagues
     */
    public function owners()
    {
        // Full Administrators are considered owners
        return User::whereHas('role', function($query) {
            $query->where('name', Role::FULL_ADMINISTRATOR);
        })->get();
    }

    /**
     * Get the admins of this league.
     * Note: This method is deprecated since we removed roles from user_leagues
     */
    public function admins()
    {
        // Full Administrators are considered admins
        return User::whereHas('role', function($query) {
            $query->where('name', Role::FULL_ADMINISTRATOR);
        })->get();
    }

    /**
     * Get the viewers of this league.
     * Note: This method is deprecated since we removed roles from user_leagues
     */
    public function viewers()
    {
        // All users with access (except Full Administrators) are viewers
        return $this->users()->whereHas('role', function($query) {
            $query->where('name', Role::STAFF);
        })->get();
    }

    /**
     * Get all transactions for this league through its users
     */
    public function transactions()
    {
        return Transaction::whereHas('userFrom', function($query) {
            $query->where('league_id', $this->id);
        })->orWhereHas('userTo', function($query) {
            $query->where('league_id', $this->id);
        })->with(['userFrom', 'userTo', 'type']);
    }

    /**
     * Refresh the league's statistics and data.
     */
    public function refreshStatistics(): bool
    {
        /** @var LeagueStatsService $service */
        $service = app(LeagueStatsService::class);
        return $service->refresh($this);
    }

    /**
     * Get the total number of market transfers (fichajes) in this league
     */
    public function getTotalTransfers(): int
    {
        return Transaction::whereHas('userTo', function($query) {
            $query->where('league_id', $this->id);
        })
        ->where(function($query) {
            $query->where('type_id', TransactionType::MARKET)
                  ->orWhere('type_id', TransactionType::TRANSFER);
        })
        ->whereNotNull('to_user_id')
        ->count();
    }

    /**
     * Get the total number of market sales (ventas) in this league
     */
    public function getTotalSales(): int
    {
        return Transaction::whereHas('userFrom', function($query) {
            $query->where('league_id', $this->id);
        })
        ->where(function($query) {
            $query->where('type_id', TransactionType::MARKET)
                  ->orWhere('type_id', TransactionType::TRANSFER);
        })
        ->whereNotNull('from_user_id')
        ->count();
    }

    /**
     * Get the total number of transfers between users in this league
     */
    public function getTotalUserTransfers(): int
    {
        return Transaction::whereHas('userFrom', function($query) {
            $query->where('league_id', $this->id);
        })
        ->whereHas('userTo', function($query) {
            $query->where('league_id', $this->id);
        })
        ->where(function($query) {
            $query->where('type_id', TransactionType::MARKET)
                  ->orWhere('type_id', TransactionType::TRANSFER);
        })
        ->whereNotNull('from_user_id')
        ->whereNotNull('to_user_id')
        ->count();
    }

    /**
     * Get detailed user transfers for this league
     */
    public function getUserTransfers()
    {
        return Transaction::whereHas('userFrom', function($query) {
            $query->where('league_id', $this->id);
        })
        ->whereHas('userTo', function($query) {
            $query->where('league_id', $this->id);
        })
        ->where(function($query) {
            $query->where('type_id', TransactionType::MARKET)
                  ->orWhere('type_id', TransactionType::TRANSFER);
        })
        ->whereNotNull('from_user_id')
        ->whereNotNull('to_user_id')
        ->with(['userFrom', 'userTo', 'type'])
        ->orderBy('date', 'desc')
        ->get()
        ->groupBy(function($transaction) {
            // Agrupa por par de usuarios (sin importar dirección)
            $users = [$transaction->userFrom->name, $transaction->userTo->name];
            sort($users);
            return implode(' ↔ ', $users);
        })
        ->map(function($transactions, $usersPair) {
            return [
                'users_pair' => $usersPair,
                'total_transfers' => $transactions->count(),
                'transfers' => $transactions
            ];
        })
        ->sortByDesc('total_transfers');
    }

    /**
     * Get purchase analysis for users in this league (admin only) - Market transactions only
     */
    public function getPurchaseAnalysis()
    {
        // Get only market purchases (fichajes) for users in this league - exclude transfers between users
        $allPurchases = Transaction::whereHas('userTo', function($query) {
            $query->where('league_id', $this->id);
        })
        ->where('type_id', TransactionType::MARKET)
        ->whereNotNull('to_user_id')
        ->whereNotNull('amount')
        ->with(['userTo'])
        ->get();

        $userAnalysis = [];

        foreach ($allPurchases as $purchase) {
            $userId = $purchase->to_user_id;
            $userName = $purchase->userTo->name;
            
            if (!isset($userAnalysis[$userId])) {
                $userAnalysis[$userId] = [
                    'user_name' => $userName,
                    'user' => $purchase->userTo,
                    'total_purchases' => 0,
                    'total_amount_paid' => 0,
                    'total_market_value' => 0,
                    'purchases_with_market_data' => 0,
                    'purchases' => [],
                    'average_overpay_percentage' => 0
                ];
            }

            // Only count and include purchases with market data available
            if ($purchase->player_id) {
                $playerPrice = $purchase->getPlayerPriceOnDate();
                
                if ($playerPrice && $playerPrice->price > 0) {
                    $marketValue = $playerPrice->getPriceInEuros();
                    $amountPaid = $purchase->amount;
                    
                    $overpayPercentage = (($amountPaid - $marketValue) / $marketValue) * 100;
                    
                    // Get price from the previous day to calculate trend
                    $previousDayPrice = null;
                    $priceChange = null;
                    $priceChangePercentage = null;
                    
                    $previousDay = $purchase->date->copy()->subDay();
                    $previousDayPriceRecord = \App\Models\PlayerPriceHistory::where('biwenger_player_id', $purchase->player_id)
                        ->whereDate('record_date', $previousDay)
                        ->first();
                    
                    if ($previousDayPriceRecord && $previousDayPriceRecord->price > 0) {
                        $previousDayPrice = $previousDayPriceRecord->getPriceInEuros();
                        $priceChange = $marketValue - $previousDayPrice;
                        $priceChangePercentage = ($priceChange / $previousDayPrice) * 100;
                    }
                    
                    // Only count purchases with market data
                    $userAnalysis[$userId]['total_purchases']++;
                    $userAnalysis[$userId]['total_amount_paid'] += $purchase->amount;
                    $userAnalysis[$userId]['purchases_with_market_data']++;
                    $userAnalysis[$userId]['total_market_value'] += $marketValue;
                    
                    $userAnalysis[$userId]['purchases'][] = [
                        'player_name' => $purchase->player_name,
                        'amount_paid' => $amountPaid,
                        'market_value' => $marketValue,
                        'overpay_amount' => $amountPaid - $marketValue,
                        'overpay_percentage' => $overpayPercentage,
                        'date' => $purchase->date,
                        'previous_day_price' => $previousDayPrice,
                        'price_change' => $priceChange,
                        'price_change_percentage' => $priceChangePercentage
                    ];
                }
            }
        }

        // Calculate median overpay percentage for each user
        foreach ($userAnalysis as $userId => &$analysis) {
            if ($analysis['total_market_value'] > 0) {
                $totalOverpay = $analysis['total_amount_paid'] - $analysis['total_market_value'];
                $analysis['total_overpay_amount'] = $totalOverpay;
                
                // Calculate median of individual overpay percentages
                $overpayPercentages = collect($analysis['purchases'])->pluck('overpay_percentage')->sort()->values();
                $count = $overpayPercentages->count();
                
                if ($count > 0) {
                    if ($count % 2 == 0) {
                        // Even number of elements: average of the two middle values
                        $analysis['average_overpay_percentage'] = ($overpayPercentages[$count/2 - 1] + $overpayPercentages[$count/2]) / 2;
                    } else {
                        // Odd number of elements: middle value
                        $analysis['average_overpay_percentage'] = $overpayPercentages[floor($count/2)];
                    }
                } else {
                    $analysis['average_overpay_percentage'] = 0;
                }
            } else {
                $analysis['average_overpay_percentage'] = 0;
                $analysis['total_overpay_amount'] = 0;
            }
        }

        // Filter out users with no purchases with market data
        $userAnalysis = array_filter($userAnalysis, function($analysis) {
            return $analysis['purchases_with_market_data'] > 0;
        });

        // Sort by average overpay percentage (descending)
        uasort($userAnalysis, function($a, $b) {
            return $b['average_overpay_percentage'] <=> $a['average_overpay_percentage'];
        });

        return collect($userAnalysis);
    }

    /**
     * Get transfer profit analysis for users in this league (admin only) - User to user sales
     */
    public function getTransferProfitAnalysis()
    {
        // Get all transfers between users in this league
        $userTransfers = Transaction::whereHas('userFrom', function($query) {
            $query->where('league_id', $this->id);
        })
        ->whereHas('userTo', function($query) {
            $query->where('league_id', $this->id);
        })
        ->where('type_id', TransactionType::TRANSFER)
        ->whereNotNull('from_user_id')
        ->whereNotNull('to_user_id')
        ->whereNotNull('amount')
        ->with(['userFrom', 'userTo'])
        ->get();

        $userProfitAnalysis = [];

        foreach ($userTransfers as $transfer) {
            $sellerId = $transfer->from_user_id;
            $sellerName = $transfer->userFrom->name;
            
            if (!isset($userProfitAnalysis[$sellerId])) {
                $userProfitAnalysis[$sellerId] = [
                    'user_name' => $sellerName,
                    'user' => $transfer->userFrom,
                    'total_sales' => 0,
                    'total_revenue' => 0,
                    'total_market_value' => 0,
                    'total_profit' => 0,
                    'sales_with_market_data' => 0,
                    'sales' => [],
                    'average_profit_percentage' => 0
                ];
            }

            $revenue = $transfer->amount;
            $userProfitAnalysis[$sellerId]['total_sales']++;
            $userProfitAnalysis[$sellerId]['total_revenue'] += $revenue;

            // Always use market value on sale date
            if ($transfer->player_id) {
                $playerPriceOnSaleDate = $transfer->getPlayerPriceOnDate();
                
                if ($playerPriceOnSaleDate && $playerPriceOnSaleDate->price > 0) {
                    $marketValue = $playerPriceOnSaleDate->getPriceInEuros();
                    $profit = $revenue - $marketValue;
                    $profitPercentage = $marketValue > 0 ? (($profit / $marketValue) * 100) : 0;
                    
                    $userProfitAnalysis[$sellerId]['sales_with_market_data']++;
                    $userProfitAnalysis[$sellerId]['total_market_value'] += $marketValue;
                    
                    $userProfitAnalysis[$sellerId]['sales'][] = [
                        'player_id' => $transfer->player_id,
                        'player_name' => $transfer->player_name,
                        'buyer_name' => $transfer->userTo->name,
                        'sale_amount' => $revenue,
                        'market_value' => $marketValue,
                        'profit_amount' => $profit,
                        'profit_percentage' => $profitPercentage,
                        'sale_date' => $transfer->date
                    ];
                } else {
                    // Sale without market value data
                    $userProfitAnalysis[$sellerId]['sales'][] = [
                        'player_id' => $transfer->player_id,
                        'player_name' => $transfer->player_name,
                        'buyer_name' => $transfer->userTo->name,
                        'sale_amount' => $revenue,
                        'market_value' => null,
                        'profit_amount' => null,
                        'profit_percentage' => null,
                        'sale_date' => $transfer->date
                    ];
                }
            } else {
                // Sale without player_id
                $userProfitAnalysis[$sellerId]['sales'][] = [
                    'player_id' => null,
                    'player_name' => $transfer->player_name,
                    'buyer_name' => $transfer->userTo->name,
                    'sale_amount' => $revenue,
                    'market_value' => null,
                    'profit_amount' => null,
                    'profit_percentage' => null,
                    'sale_date' => $transfer->date
                ];
            }
        }

        // Calculate total profit and average profit percentage for each user
        foreach ($userProfitAnalysis as $userId => &$analysis) {
            if ($analysis['total_market_value'] > 0) {
                $totalProfitFromAnalyzedSales = 0;
                foreach ($analysis['sales'] as $sale) {
                    if ($sale['profit_amount'] !== null) {
                        $totalProfitFromAnalyzedSales += $sale['profit_amount'];
                    }
                }
                
                $analysis['total_profit'] = $totalProfitFromAnalyzedSales;
                $analysis['average_profit_percentage'] = ($totalProfitFromAnalyzedSales / $analysis['total_market_value']) * 100;
            } else {
                $analysis['total_profit'] = 0;
                $analysis['average_profit_percentage'] = 0;
            }
        }

        // Filter out users with no sales with market data
        $userProfitAnalysis = array_filter($userProfitAnalysis, function($analysis) {
            return $analysis['sales_with_market_data'] > 0;
        });

        // Sort by total profit (descending)
        uasort($userProfitAnalysis, function($a, $b) {
            return $b['total_profit'] <=> $a['total_profit'];
        });

        return collect($userProfitAnalysis);
    }
}
