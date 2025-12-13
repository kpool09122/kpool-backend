<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Source\Shared\Domain\ValueObject\UserIdentifier;

interface InvoiceFactoryInterface
{
    /**
     * @param InvoiceLine[] $lines
     * @param TaxLine[] $taxLines
     */
    public function create(
        OrderIdentifier $orderIdentifier,
        UserIdentifier $customerId,
        array $lines,
        Currency $currency,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $dueDate,
        ?Discount $discount,
        array $taxLines,
    ): Invoice;
}
