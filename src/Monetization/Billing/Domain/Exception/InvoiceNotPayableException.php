<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Exception;

use DomainException;
use Throwable;

class InvoiceNotPayableException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Invoice is not payable.', 0, $previous);
    }
}
