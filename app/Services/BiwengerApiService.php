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
                            if ($transaction['type'] != 'text' && $transaction['type'] != 'adminText') {
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
     * Get detailed players data from the Biwenger API (without cache for daily updates)
     */
    public function getPlayersDetailedData(League $league): array
    {
        $baseUrl = config('biwenger.base_url');
        $endpoint = config('biwenger.endpoints.players');
        $url = $baseUrl . $endpoint;

        try {
            // Make the GET request to the API to obtain the players
            $data = $this->makeRequest($url, $league);

            // Extract detailed players data from the API
            $players = [];
            if (isset($data['data']['players'])) {
                foreach ($data['data']['players'] as $player) {
                    $players[] = [
                        'biwenger_player_id' => $player['id'],
                        'player_name' => $player['name'] ?? '',
                        'slug' => $player['slug'] ?? '',
                        'price' => (int)($player['price'] ?? 0),
                        'price_increment' => isset($player['priceIncrement']) ? (int)$player['priceIncrement'] : null,
                    ];
                }
            }

            return $players;
        } catch (\Exception $e) {
            Log::error('Failed to fetch detailed players data from Biwenger API', [
                'league_id' => $league->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get player price history from Biwenger API using player slug
     */
    public function getPlayerPriceHistory(League $league, string $playerSlug): array
    {
        $baseUrl = config('biwenger.base_url');
        $url = "{$baseUrl}/players/la-liga/{$playerSlug}";
        $query = ['lang' => 'es', 'fields' => '*,prices'];

        try {
            $data = $this->makeRequest($url, $league, $query);

            if (!isset($data['data'])) {
                Log::warning('No data found for player', ['slug' => $playerSlug]);
                return [];
            }

            $playerData = $data['data'];
            $priceHistory = [];

            // Extract basic player info and current price
            $playerInfo = [
                'biwenger_player_id' => $playerData['id'],
                'player_name' => $playerData['name'] ?? '',
                'slug' => $playerData['slug'] ?? $playerSlug,
                'current_price' => (int)($playerData['price'] ?? 0),
                'price_increment' => isset($playerData['priceIncrement']) ? (int)$playerData['priceIncrement'] : null,
            ];

            // Process historical prices if available
            if (isset($playerData['prices']) && is_array($playerData['prices'])) {
                foreach ($playerData['prices'] as $priceEntry) {
                    if (is_array($priceEntry) && count($priceEntry) >= 2) {
                        $dateString = $priceEntry[0]; // Format: YYMMDD (e.g., 240926)
                        $price = (int)$priceEntry[1];

                        // Parse date from YYMMDD format
                        $year = '20' . substr($dateString, 0, 2);
                        $month = substr($dateString, 2, 2);
                        $day = substr($dateString, 4, 2);
                        
                        try {
                            $date = \Carbon\Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}");
                            
                            $priceHistory[] = [
                                'biwenger_player_id' => $playerInfo['biwenger_player_id'],
                                'player_name' => $playerInfo['player_name'],
                                'slug' => $playerInfo['slug'],
                                'price' => $price,
                                'price_increment' => null, // Will be calculated later
                                'record_date' => $date->format('Y-m-d'),
                            ];
                        } catch (\Exception $e) {
                            Log::warning('Failed to parse date for player price history', [
                                'slug' => $playerSlug,
                                'date_string' => $dateString,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                // Calculate price increments
                $priceHistory = $this->calculatePriceIncrements($priceHistory);
            }

            return [
                'player_info' => $playerInfo,
                'price_history' => $priceHistory
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch player price history from Biwenger API', [
                'slug' => $playerSlug,
                'league_id' => $league->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Calculate price increments for historical data
     */
    private function calculatePriceIncrements(array $priceHistory): array
    {
        // Sort by date ascending to calculate increments correctly
        usort($priceHistory, function($a, $b) {
            return strcmp($a['record_date'], $b['record_date']);
        });

        for ($i = 1; $i < count($priceHistory); $i++) {
            $currentPrice = $priceHistory[$i]['price'];
            $previousPrice = $priceHistory[$i - 1]['price'];
            $priceHistory[$i]['price_increment'] = $currentPrice - $previousPrice;
        }

        return $priceHistory;
    }

    /**
     * Get all player slugs from the Biwenger API
     * Returns only players that have valid slugs for historical data import
     */
    public function getPlayerSlugs(League $league): array
    {
        try {
            $allPlayers = $this->getPlayersDetailedData($league);
            
            $playersWithSlugs = [];
            $playersWithoutSlugs = [];
            
            foreach ($allPlayers as $player) {
                if (!empty($player['slug']) && is_string($player['slug']) && strlen($player['slug']) > 0) {
                    $playersWithSlugs[] = [
                        'biwenger_player_id' => $player['biwenger_player_id'],
                        'player_name' => $player['player_name'],
                        'slug' => $player['slug'],
                        'current_price' => $player['price'],
                    ];
                } else {
                    $playersWithoutSlugs[] = [
                        'biwenger_player_id' => $player['biwenger_player_id'],
                        'player_name' => $player['player_name'],
                        'slug' => $player['slug'] ?? 'N/A',
                    ];
                }
            }
            
            if (!empty($playersWithoutSlugs)) {
                Log::info('Players without valid slugs found', [
                    'league_id' => $league->id,
                    'total_players' => count($allPlayers),
                    'players_with_slugs' => count($playersWithSlugs),
                    'players_without_slugs' => count($playersWithoutSlugs),
                    'players_without_slugs_list' => array_slice($playersWithoutSlugs, 0, 10) // Log first 10 as example
                ]);
            }
            
            return $playersWithSlugs;
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch player slugs from Biwenger API', [
                'league_id' => $league->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
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

    /**
     * Get user's current squad from Biwenger API
     * 
     * @param League $league
     * @param int $userId Biwenger user ID
     * @return array Array of players with id, owner.date, owner.price
     */
    public function getUserSquad(League $league, int $userId): array
    {
        $baseUrl = config('biwenger.base_url');
        $url = "{$baseUrl}/user/{$userId}?fields=*,lineup(type,playersID,reservesID,captain,striker,coach,date),players(id,owner),market,offers,-trophies";

        try {
            $data = $this->makeRequest($url, $league);
            
            if (!isset($data['data']['players'])) {
                Log::warning('No players found for user', [
                    'user_id' => $userId,
                    'league_id' => $league->id
                ]);
                return [];
            }

            return $data['data']['players'];
        } catch (\Exception $e) {
            Log::error('Failed to fetch user squad from Biwenger API', [
                'user_id' => $userId,
                'league_id' => $league->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get player's price history from Biwenger API
     * 
     * @param League $league
     * @param int $playerId Biwenger player ID
     * @return array Array with format [YYMMDD => price]
     */
    public function getPlayerPrices(League $league, int $playerId): array
    {
        $baseUrl = config('biwenger.base_url');
        $url = "{$baseUrl}/players/la-liga/{$playerId}?lang=es&fields=*%2Cprices";

        try {
            $data = $this->makeRequest($url, $league);
            
            if (!isset($data['data']['prices'])) {
                Log::warning('No price history found for player', [
                    'player_id' => $playerId,
                    'league_id' => $league->id
                ]);
                return [];
            }

            // Convert prices array to associative array for easier lookup
            // Format: [YYMMDD => price]
            $prices = [];
            foreach ($data['data']['prices'] as $priceEntry) {
                [$dateKey, $price] = $priceEntry;
                $prices[$dateKey] = $price;
            }

            return $prices;
        } catch (\Exception $e) {
            Log::error('Failed to fetch player prices from Biwenger API', [
                'player_id' => $playerId,
                'league_id' => $league->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
