<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Exception;

use DomainException;
use Throwable;

class EmptyInvoiceLinesException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('At least one product line is required.', 0, $previous);
    }
}
