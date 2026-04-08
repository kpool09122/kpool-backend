<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Exception;

use DomainException;
use Throwable;

class RefundCurrencyMismatchException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Refund currency must match payment currency.', 0, $previous);
    }
}
