<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Exception;

use DomainException;
use Throwable;

class InvalidPaymentMethodException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Payment method is not available for this payment.', 0, $previous);
    }
}
