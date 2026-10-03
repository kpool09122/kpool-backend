<?php

namespace Fixtures\LogFacade;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Log as LaravelLog;
use Psr\Log\LoggerInterface;

function logMessages(LoggerInterface $logger): void
{
    Log::info('forbidden');
    LaravelLog::warning('forbidden alias');
    \Illuminate\Support\Facades\Log::error('forbidden fully qualified name');
    \Log::info('forbidden global alias');
    $logger->info('allowed');
}

namespace Fixtures\UnrelatedLogger;

function unrelatedLog(): void
{
    Log::info('allowed unrelated class');
}

class Log
{
    public static function info(string $message): void
    {
    }
}
