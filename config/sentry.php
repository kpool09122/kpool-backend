<?php

declare(strict_types=1);

use Application\Http\Exceptions\Handler;
use Sentry\Event;
use Sentry\EventHint;

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'environment' => config('app.env'),

    'traces_sample_rate' => 0.0,

    'send_default_pii' => false,

    'before_send' => static function (Event $event, ?EventHint $hint): ?Event {
        $exception = $hint?->exception;

        if (!$exception instanceof \Throwable) {

            return null;
        }

        return Handler::shouldReportToSentry($exception) ? $event : null;
    },
];
