<?php

declare(strict_types=1);

use Application\Http\Exceptions\Handler;
use Sentry\Event;
use Sentry\EventHint;

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'environment' => env('SENTRY_ENVIRONMENT'),

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null
        ? null
        : (float) env('SENTRY_TRACES_SAMPLE_RATE'),

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    'before_send' => static function (Event $event, ?EventHint $hint): ?Event {
        $exception = $hint?->exception;

        if (!$exception instanceof \Throwable) {

            return null;
        }

        return Handler::shouldReportToSentry($exception) ? $event : null;
    },
];
