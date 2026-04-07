<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Exception;

use DomainException;
use Throwable;

class PaymentCurrencyMismatchForMatchingException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Payment currency does not match invoice.', 0, $previous);
    }
}
