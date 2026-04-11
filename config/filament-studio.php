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

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Configure how Studio registers permissions with spatie/laravel-permission.
    | Set 'auto_register' to false to prevent automatic permission seeding.
    | Set 'guard' to specify which auth guard the permissions belong to.
    |
    */
    'permissions' => [
        'auto_register' => env('STUDIO_PERMISSIONS_AUTO_REGISTER', true),
        'guard' => env('STUDIO_PERMISSIONS_GUARD'),
    ],
];
