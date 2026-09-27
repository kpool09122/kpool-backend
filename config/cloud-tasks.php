<?php

declare(strict_types=1);

return [
    // Register the authenticated route in bootstrap/app.php instead of the package route.
    'disable_task_handler' => true,
    'uri' => 'internal/queue/default',
    'handler_enabled' => (bool) env('CLOUD_TASKS_HANDLER_ENABLED', false),
    'client_options' => ['transport' => 'rest'],
];
