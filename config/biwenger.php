<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Biwenger API Configuration
    |--------------------------------------------------------------------------
    */

    'base_url' => env('BIWENGER_BASE_URL', 'https://biwenger.as.com/api/v2'),
    
    'endpoints' => [
        'league' => '/league',
        'players' => '/competitions/la-liga/data',
    ],

    'timeout' => env('BIWENGER_TIMEOUT', 30),
    
    'retry' => [
        'times' => env('BIWENGER_RETRY_TIMES', 3),
        'sleep' => env('BIWENGER_RETRY_SLEEP', 1000), // milliseconds
    ],

    'rate_limit' => [
        'max_attempts' => env('BIWENGER_RATE_LIMIT', 60),
        'decay_minutes' => env('BIWENGER_RATE_DECAY', 1),
    ],

    'cache' => [
        'ttl' => env('BIWENGER_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'biwenger_',
    ],

    'logging' => [
        'requests' => env('BIWENGER_LOG_REQUESTS', false),
        'errors' => env('BIWENGER_LOG_ERRORS', true),
    ],
];
