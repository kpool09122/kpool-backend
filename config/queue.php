<?php

declare(strict_types=1);

$defaultConnection = env('QUEUE_CONNECTION', in_array(env('APP_ENV', 'production'), ['local', 'testing'], true) ? 'redis' : 'sqs');
$workQueue = env('SQS_QUEUE_URL', env('SQS_QUEUE', 'default'));

return [
    'default' => $defaultConnection,
    // #673 provisions one work queue: all logical producers share its task-role permissions.
    'routing' => [
        'webhook' => $defaultConnection === 'sqs' ? $workQueue : 'webhook',
        'settlement' => $defaultConnection === 'sqs' ? $workQueue : 'settlement',
    ],

    'connections' => [
        'sqs' => [
            'driver' => 'sqs',
            // No static credentials: the AWS SDK uses the ECS task role credential chain.
            'prefix' => env('SQS_PREFIX', ''),
            'queue' => $workQueue,
            'suffix' => env('SQS_SUFFIX', ''),
            'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
            'after_commit' => false,
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 300,
            'block_for' => 5,
            'after_commit' => false,
        ],
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table' => 'failed_jobs',
    ],
];
