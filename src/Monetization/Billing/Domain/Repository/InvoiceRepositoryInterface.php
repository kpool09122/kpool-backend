<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Repository;

use Source\Monetization\Billing\Domain\Entity\Invoice;

interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void;
}
