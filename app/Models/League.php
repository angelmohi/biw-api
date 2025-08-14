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
}
