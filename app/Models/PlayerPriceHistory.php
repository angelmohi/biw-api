<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PlayerPriceHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'player_price_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'biwenger_player_id',
        'player_name',
        'slug',
        'price',
        'price_increment',
        'record_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'record_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('record_date', $date);
    }

    /**
     * Scope for a specific player
     */
    public function scopeForPlayer($query, $playerId)
    {
        return $query->where('biwenger_player_id', $playerId);
    }

    /**
     * Scope for the latest records
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('record_date', 'desc');
    }

    /**
     * Get the latest price record for a specific player
     */
    public static function getLatestPriceForPlayer($playerId)
    {
        return static::forPlayer($playerId)
            ->latest()
            ->first();
    }

    /**
     * Get price history for a specific player
     */
    public static function getPriceHistoryForPlayer($playerId, $days = 30)
    {
        $fromDate = Carbon::now()->subDays($days);
        
        return static::forPlayer($playerId)
            ->where('record_date', '>=', $fromDate)
            ->orderBy('record_date', 'asc')
            ->get();
    }

    /**
     * Get top price increasers for a specific date
     */
    public static function getTopPriceIncreasers($date = null, $limit = 10)
    {
        $date = $date ?: Carbon::today();
        
        return static::forDate($date)
            ->whereNotNull('price_increment')
            ->where('price_increment', '>', 0)
            ->orderBy('price_increment', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top price decreasers for a specific date
     */
    public static function getTopPriceDecreasers($date = null, $limit = 10)
    {
        $date = $date ?: Carbon::today();
        
        return static::forDate($date)
            ->whereNotNull('price_increment')
            ->where('price_increment', '<', 0)
            ->orderBy('price_increment', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get price in euros (already stored in euros)
     */
    public function getPriceInEuros()
    {
        return $this->price ? $this->price : 0;
    }

    /**
     * Get price increment in euros (already stored in euros)
     */
    public function getPriceIncrementInEuros()
    {
        return $this->price_increment ? $this->price_increment : 0;
    }
}
