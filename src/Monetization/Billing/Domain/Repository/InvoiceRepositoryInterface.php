<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Repository;

use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;

interface InvoiceRepositoryInterface
{
    public function findById(InvoiceIdentifier $invoiceIdentifier): ?Invoice;

    public function save(Invoice $invoice): void;
}
