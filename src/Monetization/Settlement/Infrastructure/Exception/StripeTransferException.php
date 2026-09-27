<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Exception;

use RuntimeException;
use Throwable;

class StripeTransferException extends RuntimeException
{
    public function __construct(
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
