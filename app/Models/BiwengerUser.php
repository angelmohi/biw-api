<?php

namespace App\Models;

class BiwengerUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'biwenger_user';

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
     * Refresh the user's statistics based on the provided data.
     */
    public function refreshStatistics(array $users): BiwengerUser
    {
        $userData = $users[$this->biwenger_id] ?? [];
        
        $this->position = $userData['position'] ?? 0;
        $this->points = $userData['points'] ?? 0;
        
        if (isset($userData['icon'])) {
            $this->icon = $userData['icon'];
        }

        $this->save();
        $this->touch();

        return $this;
    }

    /**
     * Get all balance records for this user
     */
    public function balances()
    {
        return $this->hasMany(BiwengerUserBalance::class, 'user_id');
    }

    /**
     * Get the current (most recent) balance for this user
     */
    public function getCurrentBalance(): ?BiwengerUserBalance
    {
        return BiwengerUserBalance::getCurrentBalance($this);
    }

    /**
     * Get balance history for this user
     */
    public function getBalanceHistory(string $startDate = null, string $endDate = null): array
    {
        return BiwengerUserBalance::getBalanceHistory($this, $startDate, $endDate);
    }

    /**
     * Get balance for a specific date
     */
    public function getBalanceForDate(string $date): ?BiwengerUserBalance
    {
        return BiwengerUserBalance::getBalanceForDate($this, $date);
    }

    /**
     * Get the league
     */
    public function league()
    {
        return $this->belongsTo(League::class, 'league_id');
    }
}
