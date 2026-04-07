<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

interface CreateInvoiceInterface
{
    public function process(CreateInvoiceInputPort $input, CreateInvoiceOutputPort $output): void;
}
