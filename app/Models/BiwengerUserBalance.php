<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiwengerUserBalance extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'biwenger_user_balance';

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
     * Get the user that owns the balance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(BiwengerUser::class, 'user_id');
    }

    /**
     * Update the user's balance for the current date
     */
    public static function updateBalance(BiwengerUser $user, array $biwengerUsers): BiwengerUserBalance
    {
        $initialBalance = $user->initial_balance;
        $today = now()->format('Y-m-d');

        $marketTransactions = Transaction::where('to_user_id', $user->id)
            ->where('type_id', TransactionType::MARKET)
            ->get();

        $roundFinishedTransactions = Transaction::where('to_user_id', $user->id)
            ->where('type_id', TransactionType::ROUND_FINISHED)
            ->get();

        $transferPositiveTransactions = Transaction::where('from_user_id', $user->id)
            ->where('type_id', TransactionType::TRANSFER)
            ->get();

        $transferNegativeTransactions = Transaction::where('to_user_id', $user->id)
            ->where('type_id', TransactionType::TRANSFER)
            ->get();

        $clauseIncrementTransactions = Transaction::where('from_user_id', $user->id)
            ->where('type_id', TransactionType::CLAUSE_INCREMENT)
            ->get();

        $amountObtained = 0;
        $amountSpent = 0;

        foreach ($marketTransactions as $transaction) {
            $amountSpent += $transaction->amount;
        }
        foreach ($roundFinishedTransactions as $transaction) {
            $amountObtained += $transaction->amount;
        }
        foreach ($transferPositiveTransactions as $transaction) {
            $amountObtained += $transaction->amount;
        }
        foreach ($transferNegativeTransactions as $transaction) {
            $amountSpent += $transaction->amount;
        }
        foreach ($clauseIncrementTransactions as $transaction) {
            $amountSpent += $transaction->amount;
        }

        $cash = $initialBalance + $amountObtained - $amountSpent;
        $maximumBid = ($biwengerUsers[$user->biwenger_id]['teamValue'] * 0.25) + $cash;
        $balance = $cash + $biwengerUsers[$user->biwenger_id]['teamValue'];

        $userBalance = BiwengerUserBalance::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();

        if (!$userBalance) {
            $userBalance = new BiwengerUserBalance();
            $userBalance->user_id = $user->id;
            $userBalance->created_at = now();
        }

        $userBalance->cash = $cash;
        $userBalance->team_value = $biwengerUsers[$user->biwenger_id]['teamValue'];
        $userBalance->team_size = $biwengerUsers[$user->biwenger_id]['teamSize'];
        $userBalance->maximum_bid = $maximumBid;
        $userBalance->balance = $balance;
        $userBalance->updated_at = now();
        $userBalance->save();

        return $userBalance;
    }

    /**
     * Get the current (most recent) balance for a user
     */
    public static function getCurrentBalance(BiwengerUser $user): ?BiwengerUserBalance
    {
        return BiwengerUserBalance::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get the current balance for all users in a league
     */
    public static function getCurrentBalancesForLeague(League $league): array
    {
        $users = $league->biwengerUsers;
        $balances = [];

        foreach ($users as $user) {
            $currentBalance = self::getCurrentBalance($user);
            if ($currentBalance) {
                $balances[$user->id] = $currentBalance;
            }
        }

        return $balances;
    }

    /**
     * Get balance history for a user within a date range
     */
    public static function getBalanceHistory(BiwengerUser $user, string $startDate = null, string $endDate = null): array
    {
        $query = BiwengerUserBalance::where('user_id', $user->id)
            ->orderBy('created_at', 'asc');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get()->toArray();
    }

    /**
     * Get balance for a specific date
     */
    public static function getBalanceForDate(BiwengerUser $user, string $date): ?BiwengerUserBalance
    {
        return BiwengerUserBalance::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->first();
    }
}
