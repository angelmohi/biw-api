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
        ->where('type_id', TransactionType::MARKET)
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
        ->where('type_id', TransactionType::MARKET)
        ->whereNotNull('from_user_id')
        ->count();
    }
}
