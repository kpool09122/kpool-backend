<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Exception;

use DomainException;
use Throwable;

class RefundExceedsCapturedAmountException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Refund exceeds captured amount.', 0, $previous);
    }
}
