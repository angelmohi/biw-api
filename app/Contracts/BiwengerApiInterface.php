<?php

namespace App\Contracts;

use App\Models\League;

interface BiwengerApiInterface
{
    /**
     * Get league data (users, standings, etc.) from Biwenger API
     */
    public function getLeagueData(League $league): array;

    /**
     * Get users from Biwenger API
     */
    public function getUsers(League $league): array;

    /**
     * Get team values from Biwenger API
     */
    public function getTeamValues(League $league): array;

    /**
     * Get the transactions from Biwenger API
     */
    public function getTransactions(League $league, $to): array;

    /**
     * Get players from the Biwenger API using cache
     */
    public function getPlayers(League $league): array;

    /**
     * Get the name player from Biwenger API using cache
     */
    public function getPlayerName(League $league, $playerId): string;

    /**
     * Get user's current squad from Biwenger API
     */
    public function getUserSquad(League $league, int $userId): array;

    /**
     * Get player's price history from Biwenger API
     */
    public function getPlayerPrices(League $league, int $playerId): array;
}
