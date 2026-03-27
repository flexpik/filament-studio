<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for all database tables created by Filament Studio.
    | Change this if you need to avoid conflicts with existing tables.
    |
    */
    'table_prefix' => 'studio_',

    /*
    |--------------------------------------------------------------------------
    | REST API
    |--------------------------------------------------------------------------
    |
    | Configure the auto-generated REST API for Studio collections.
    |
    */
    'api' => [
        'enabled' => env('STUDIO_API_ENABLED', false),
        'prefix' => env('STUDIO_API_PREFIX', 'api/studio'),
        'rate_limit' => env('STUDIO_API_RATE_LIMIT', 60),
    ],
];
