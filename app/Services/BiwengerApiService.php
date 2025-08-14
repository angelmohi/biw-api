<?php

namespace App\Services;

use App\Contracts\BiwengerApiInterface;
use App\Models\League;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiwengerApiService implements BiwengerApiInterface
{
    /**
     * Make a request to Biwenger API
     */
    private function makeRequest(string $url, League $league, array $query = []): array
    {
        try {
            $httpClient = Http::timeout(config('biwenger.timeout'))
                ->retry(
                    config('biwenger.retry.times'),
                    config('biwenger.retry.sleep')
                )
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $league->bearer_token,
                    'X-League' => $league->bearer_league,
                    'X-User' => $league->bearer_user,
                    'Accept' => 'application/json'
                ]);

            $response = empty($query) ? $httpClient->get($url) : $httpClient->get($url, $query);

            $this->logRequest($url, $query, $response);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            $this->logError($url, $response);
            return [];

        } catch (\Exception $e) {
            Log::error('Biwenger API Exception', [
                'url' => $url,
                'query' => $query,
                'league_id' => $league->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Log successful requests (optional, for debugging)
     */
    private function logRequest(string $url, array $query, Response $response): void
    {
        if (config('biwenger.logging.requests')) {
            Log::debug('Biwenger API Request', [
                'url' => $url,
                'query' => $query,
                'status' => $response->status(),
                'response_size' => strlen($response->body())
            ]);
        }
    }

    /**
     * Log API errors with detailed information
     */
    private function logError(string $url, Response $response): void
    {
        if (config('biwenger.logging.errors')) {
            Log::error('Biwenger API Error', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
                'headers' => $response->headers()
            ]);
        }
    }

    /**
     * Get league data (users, standings, etc.) from Biwenger API
     */
    public function getLeagueData(League $league): array
    {
        if (!$league->biwenger_id) {
            Log::warning('League missing biwenger_id', ['league_id' => $league->id]);
            return [];
        }

        $cacheKey = config('biwenger.cache.prefix') . "league_{$league->biwenger_id}";
        $cacheTtl = config('biwenger.cache.ttl');
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($league) {
            $baseUrl = config('biwenger.base_url');
            $endpoint = config('biwenger.endpoints.league');
            $url = $baseUrl . $endpoint . "/{$league->biwenger_id}?include=all&fields=*,standings,tournaments,group,settings(description)";

            try {
                $data = $this->makeRequest($url, $league);
                return $data['data'] ?? [];
            } catch (\Exception $e) {
                Log::error('Failed to fetch league data from Biwenger API', [
                    'league_id' => $league->id,
                    'biwenger_id' => $league->biwenger_id,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get users from Biwenger API
     */
    public function getUsers(League $league): array
    {
        $leagueData = $this->getLeagueData($league);
        
        $users = [];
        if (isset($leagueData['standings'])) {
            foreach ($leagueData['standings'] as $player) {
                $users[$player['id']] = [
                    'biwenger_id' => $player['id'] ?? 0,
                    'name' => $player['name'] ?? '',
                    'points' => $player['points'] ?? 0,
                    'teamValue' => $player['teamValue'] ?? 0,
                    'teamSize' => $player['teamSize'] ?? 0,
                    'position' => $player['position'] ?? 0,
                    'icon' => $this->buildIconUrl($player['icon'] ?? ''),
                ];
            }
        }

        return $users;
    }

    /**
     * Build complete icon URL from Biwenger icon path
     */
    public function buildIconUrl(string $icon): string
    {
        if (empty($icon)) {
            return '';
        }

        if (strpos($icon, 'http') === 0) {
            return $icon;
        }

        if (strpos($icon, 'icons/') === 0) {
            return 'https://cdn.biwenger.com/cdn-cgi/image/f=avif/' . $icon;
        }

        if (strpos($icon, 'i/u/') === 0) {
            return 'https://cdn.biwenger.com/' . $icon;
        }

        return 'https://cdn.biwenger.com/' . $icon;
    }

    /**
     * Get team values from Biwenger API
     */
    public function getTeamValues(League $league): array
    {
        $leagueData = $this->getLeagueData($league);
        
        $teamValues = [];
        if (isset($leagueData['standings'])) {
            foreach ($leagueData['standings'] as $player) {
                $teamValues[$player['id']] = [
                    'value' => $player['teamValue'] ?? 0,
                    'size' => $player['teamSize'] ?? 0,
                ];
            }
        }

        return $teamValues;
    }

    /**
     * Get the transactions from Biwenger API
     */
    public function getTransactions(League $league, $to): array
    {
        if (!$league->biwenger_id) {
            Log::warning('League missing biwenger_id for transactions', ['league_id' => $league->id]);
            return [];
        }

        $baseUrl = config('biwenger.base_url');
        $endpoint = config('biwenger.endpoints.league');
        $url = $baseUrl . $endpoint . "/{$league->biwenger_id}/board";
        $transactions = [];
        $offset = 0;
        $limit = 500; // Biwenger has a limit of 500 transactions per call
        $totalTransactions = 0;
        $foundOlderTransaction = false;

        try {
            do {
                // Make the GET request to the API with offset and limit
                $data = $this->makeRequest($url, $league, ['offset' => $offset, 'limit' => $limit]);

                // Check if there are transactions in the response
                if (isset($data['data']) && count($data['data']) > 0) {
                    foreach ($data['data'] as $transaction) {
                        // Filter only transactions newer than or equal to the last update
                        // Using >= to include transactions from the same timestamp
                        if ($transaction['date'] >= $to) {
                            $transactions[] = $transaction;
                        } else {
                            // If we find a transaction older than $to, 
                            // we can stop processing as transactions are ordered by date
                            if ($transaction['type'] != 'text') {
                                $foundOlderTransaction = true;
                                break;
                            }
                        }
                    }
                    $totalTransactions = count($data['data']);
                } else {
                    // No more transactions, stop the loop
                    $totalTransactions = 0;
                }

                // Increase the offset for the next call
                $offset += $limit;
    
            } while ($totalTransactions == $limit && !$foundOlderTransaction);

            return array_reverse($transactions);

        } catch (\Exception $e) {
            Log::error('Failed to fetch transactions from Biwenger API', [
                'league_id' => $league->id,
                'biwenger_id' => $league->biwenger_id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get players from the Biwenger API using cache
     */
    public function getPlayers(League $league): array
    {
        $cacheKey = config('biwenger.cache.prefix') . "players_la_liga";
        $cacheTtl = config('biwenger.cache.ttl');
        
        return Cache::remember($cacheKey, $cacheTtl, function () use ($league) {
            $baseUrl = config('biwenger.base_url');
            $endpoint = config('biwenger.endpoints.players');
            $url = $baseUrl . $endpoint;

            try {
                // Make the GET request to the API to obtain the players
                $data = $this->makeRequest($url, $league);

                // Extract players from the API
                $players = [];
                if (isset($data['data']['players'])) {
                    foreach ($data['data']['players'] as $player) {
                        $players[$player['id']] = $player['name'];
                    }
                }

                return $players;
            } catch (\Exception $e) {
                Log::error('Failed to fetch players from Biwenger API', [
                    'league_id' => $league->id,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Get the name player from Biwenger API using cache
     */
    public function getPlayerName(League $league, $playerId): string
    {
        if (empty($playerId)) {
            Log::warning('Empty player ID provided to getPlayerName');
            return '';
        }

        // First try to get from cached players data
        $players = $this->getPlayers($league);
        
        if (isset($players[$playerId])) {
            return $players[$playerId];
        }

        Log::warning('Player not found in cached data', [
            'player_id' => $playerId,
            'league_id' => $league->id
        ]);
        
        return '';
    }
}
