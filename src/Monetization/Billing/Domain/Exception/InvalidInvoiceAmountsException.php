<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Exception;

use DomainException;

class InvalidInvoiceAmountsException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
