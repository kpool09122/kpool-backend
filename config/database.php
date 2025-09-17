<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => env('DB_CONNECTION', 'pgsql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'kpool'),
            'username' => env('DB_USERNAME', 'kpool'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'testing' => [
            'driver' => 'pgsql',
            'url' => env('TEST_DATABASE_URL'),
            'host' => env('TEST_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('TEST_DB_PORT', env('DB_PORT', '5432')),
            'database' => env('TEST_DB_DATABASE', 'kpool_test'),
            'username' => env('TEST_DB_USERNAME', env('DB_USERNAME', 'kpool')),
            'password' => env('TEST_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */
    'migrations' => 'migrations',

]; 