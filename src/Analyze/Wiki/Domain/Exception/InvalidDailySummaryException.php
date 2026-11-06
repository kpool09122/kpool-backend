<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\Exception;

use LogicException;
use Throwable;

class InvalidDailySummaryException extends LogicException
{
    public function __construct(string $message = 'Invalid daily summary.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
