<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Exception;

use DomainException;
use Throwable;

class PaymentAmountMismatchForMatchingException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Payment amount does not match invoice total.', 0, $previous);
    }
}
