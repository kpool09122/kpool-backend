<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Exception;

use DomainException;
use Throwable;

class PaymentOrderMismatchException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Invoice and Payment are not for the same order.', 0, $previous);
    }
}
