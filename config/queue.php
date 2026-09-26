<?php

declare(strict_types=1);

return [
    'connections' => [
        'passkey_recovery' => [
            'driver' => in_array(env('APP_ENV', 'production'), ['local', 'testing'], true) ? 'redis' : 'cloudtasks',
            'queue' => env('PASSKEY_RECOVERY_QUEUE', 'passkey-recovery'),
            'after_commit' => false,

            // Redis (local / testing)
            'connection' => 'default',
            'retry_after' => 90,
            'block_for' => null,

            // Google Cloud Tasks
            'project' => env('CLOUD_TASKS_PROJECT', ''),
            'location' => env('CLOUD_TASKS_LOCATION', ''),
            'handler' => env('CLOUD_TASKS_HANDLER', ''),
            'service_account_email' => env('CLOUD_TASKS_SERVICE_EMAIL', ''),
            'app_engine' => false,
            'backoff' => 60,
        ],
    ],
];
