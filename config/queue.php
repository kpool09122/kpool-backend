<?php

declare(strict_types=1);

return [
    'default' => env('QUEUE_CONNECTION', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true) ? 'redis' : 'cloudtasks'),

    'connections' => [
        'cloudtasks' => [
            'driver' => 'cloudtasks',
            'queue' => env('CLOUD_TASKS_QUEUE', 'default'),
            'after_commit' => false,

            'project' => env('CLOUD_TASKS_PROJECT', ''),
            'location' => env('CLOUD_TASKS_LOCATION', ''),
            'handler' => env('CLOUD_TASKS_HANDLER', ''),
            'service_account_email' => env('CLOUD_TASKS_SERVICE_EMAIL', ''),
            'app_engine' => false,
            'backoff' => 60,
        ],
    ],
];
