<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Exception;

use RuntimeException;
use Throwable;

class IncompleteDailyMetricsException extends RuntimeException
{
    public function __construct(string $message = 'Daily metric data is incomplete.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
