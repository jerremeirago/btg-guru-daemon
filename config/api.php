<?php

return [
    'endpoints' => [
        'rapidapi' => [
            'driver' => 'rapidapi',
            'host' => env('RAPIDAPI_BASE_URL'),
            'api_key' => env('RAPIDAPI_KEY'),
            'endpoint' => env('RAPIDAPI_HOST'),
        ],
        'goalserve' => [
            'driver' => 'goalserve',
            'host' => env('GOALSERVE_BASE_URL'),
            'api_key' => '',
        ],
    ],
];
