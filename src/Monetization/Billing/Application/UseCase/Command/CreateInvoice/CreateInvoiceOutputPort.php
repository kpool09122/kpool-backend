<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

use Source\Monetization\Billing\Domain\Entity\Invoice;

interface CreateInvoiceOutputPort
{
    public function setInvoice(Invoice $invoice): void;
}
