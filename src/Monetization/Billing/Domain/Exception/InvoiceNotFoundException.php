<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Exception;

use DomainException;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;

class InvoiceNotFoundException extends DomainException
{
    public function __construct(InvoiceIdentifier $invoiceIdentifier)
    {
        parent::__construct(
            sprintf('Invoice not found: %s', (string) $invoiceIdentifier)
        );
    }
}
