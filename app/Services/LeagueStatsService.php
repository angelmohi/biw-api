<?php

namespace App\Services;

use App\Contracts\BiwengerApiInterface;
use App\Models\BiwengerUser;
use App\Models\BiwengerUserBalance;
use App\Models\League;
use App\Models\Transaction;
use App\Models\TransactionType;

class LeagueStatsService
{
    private BiwengerApiInterface $biwengerApi;

    public function __construct(BiwengerApiInterface $biwengerApi)
    {
        $this->biwengerApi = $biwengerApi;
    }

    /**
     * Refresh the league's statistics and data.
     */
    public function refresh(League $league): bool
    {
        $to = $league->start_date ? strtotime($league->start_date) : time();

        $lastTransaction = $league->transactions()->orderBy('id', 'desc')->first() ?? null;
        $to = $lastTransaction ? strtotime($lastTransaction->date) - 250000 : $to;

        // Get the players from the Biwenger API
        $players = $this->biwengerApi->getPlayers($league);

        // Get the transactions from the Biwenger API
        $transactions = $this->biwengerApi->getTransactions($league, $to);

        foreach ($transactions as $transaction) {
            switch ($transaction['type']) {
                case 'transfer':
                    $this->processTransfer($transaction, $players, $league);
                    break;

                case 'market':
                    $this->processMarket($transaction, $players, $league);
                    break;

                case 'roundFinished':
                    $this->processRoundFinished($transaction);
                    break;
            }
        }

        // Get the team values from the Biwenger API
        $biwengerUsers = $this->biwengerApi->getUsers($league);

        // Update the users' statistics
        $users = BiwengerUser::where('league_id', $league->id)->get();
        foreach ($users as $user) {
            $user->refreshStatistics($biwengerUsers);
            BiwengerUserBalance::updateBalance($user, $biwengerUsers);
        }
        
        return true;
    }

    /**
     * Process transfer transactions
     */
    private function processTransfer($transaction, $players, $league): void
    {
        foreach ($transaction['content'] as $transfer) {
            // Generate unique hash for this transfer
            $hash = $this->generateTransactionHash($transaction, TransactionType::TRANSFER, $transfer);
            
            // Skip if transaction already exists
            if ($this->transactionExists($hash)) {
                continue;
            }

            $amount = $transfer['amount'];

            // If it is a sale between players
            if (isset($transfer['to'])) {
                $from = $transfer['from']['id'];
                $to = $transfer['to']['id'];

                $fromUser = BiwengerUser::where('biwenger_id', $from)
                    ->where('league_id', $league->id)
                    ->first();
                $toUser = BiwengerUser::where('biwenger_id', $to)
                    ->where('league_id', $league->id)
                    ->first();
            } else {
                // If it's a sale to the market
                $from = $transfer['from']['id'];
                $fromUser = BiwengerUser::where('biwenger_id', $from)
                    ->where('league_id', $league->id)
                    ->first();
                $toUser = null;
            }

            if (!$fromUser) {
                continue; // Skip if user not found
            }

            $newTransaction = new Transaction();
            $newTransaction->transaction_hash = $hash;
            $newTransaction->type_id = TransactionType::TRANSFER;
            $newTransaction->amount = $amount;
            $newTransaction->player_id = $transfer['player'];
            $newTransaction->player_name = $players[$transfer['player']] ?? 'N/A';
            $newTransaction->from_user_id = $fromUser->id;
            $newTransaction->to_user_id = $toUser->id ?? null;
            $newTransaction->date = date('Y-m-d H:i:s', $transaction['date']);
            $newTransaction->save();
        }
    }

    /**
     * Process market transactions
     */
    private function processMarket($transaction, $players, $league): void
    {
        foreach ($transaction['content'] as $purchase) {
            // Generate unique hash for this purchase
            $hash = $this->generateTransactionHash($transaction, TransactionType::MARKET, $purchase);
            
            // Skip if transaction already exists
            if ($this->transactionExists($hash)) {
                continue;
            }

            $to = $purchase['to']['id'];
            $amount = $purchase['amount'];

            $toUser = BiwengerUser::where('biwenger_id', $to)
                ->where('league_id', $league->id)
                ->first();

            if (!$toUser) {
                continue; // Skip if user not found
            }

            $newTransaction = new Transaction();
            $newTransaction->transaction_hash = $hash;
            $newTransaction->type_id = TransactionType::MARKET;
            $newTransaction->amount = $amount;
            $newTransaction->player_id = $purchase['player'];
            $newTransaction->player_name = $players[$purchase['player']] ?? 'N/A';
            $newTransaction->to_user_id = $toUser->id;
            $newTransaction->date = date('Y-m-d H:i:s', $transaction['date']);
            $newTransaction->save();
        }
    }

    /**
     * Process round finished transactions
     */
    private function processRoundFinished($transaction): void
    {
        if (!isset($transaction['content']['round']['id'])) {
            return;
        }

        foreach ($transaction['content']['results'] as $result) {
            // Generate unique hash for this round result
            $hash = $this->generateTransactionHash($transaction, TransactionType::ROUND_FINISHED, $result);
            
            // Skip if transaction already exists
            if ($this->transactionExists($hash)) {
                continue;
            }

            $userId = $result['user']['id'];
            $bonus = $result['bonus'] ?? 0;

            $user = BiwengerUser::where('biwenger_id', $userId)->first();

            if (!$user) {
                continue; // Skip if user not found
            }

            $newTransaction = new Transaction();
            $newTransaction->transaction_hash = $hash;
            $newTransaction->type_id = TransactionType::ROUND_FINISHED;
            $newTransaction->description = $transaction['content']['round']['name'];
            $newTransaction->amount = $bonus;
            $newTransaction->to_user_id = $user->id;
            $newTransaction->date = date('Y-m-d H:i:s', $transaction['date']);
            $newTransaction->save();
        }
    }

    /**
     * Generate a unique hash for a transaction to avoid duplicates
     */
    private function generateTransactionHash($transaction, $type, $contentItem = null): string
    {
        $data = [
            'date' => $transaction['date'],
            'type' => $type,
        ];

        switch ($type) {
            case TransactionType::TRANSFER:
                $data['player_id'] = $contentItem['player'];
                $data['amount'] = $contentItem['amount'];
                $data['from'] = $contentItem['from']['id'];
                $data['to'] = $contentItem['to']['id'] ?? null;
                break;

            case TransactionType::MARKET:
                $data['player_id'] = $contentItem['player'];
                $data['amount'] = $contentItem['amount'];
                $data['to'] = $contentItem['to']['id'];
                break;

            case TransactionType::ROUND_FINISHED:
                $data['round_id'] = $transaction['content']['round']['id'];
                $data['amount'] = $contentItem['bonus'] ?? 0;
                $data['user'] = $contentItem['user']['id'];
                break;
        }

        return hash('sha256', json_encode($data));
    }

    /**
     * Check if a transaction already exists based on its unique hash
     */
    private function transactionExists(string $hash): bool
    {
        return Transaction::where('transaction_hash', $hash)->exists();
    }
}
