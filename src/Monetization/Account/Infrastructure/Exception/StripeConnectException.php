<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Exception;

use Exception;

class StripeConnectException extends Exception
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
