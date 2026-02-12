<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Exception;

use DomainException;
use Throwable;

class InvalidPaymentStatusException extends DomainException
{
    public function __construct(
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
