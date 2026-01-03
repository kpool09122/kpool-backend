<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Infrastructure\Factory;

use DateTimeImmutable;
use DomainException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\Factory\InvoiceFactoryInterface;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

readonly class InvoiceFactory implements InvoiceFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    /**
     * @param InvoiceLine[] $lines
     * @param TaxLine[] $taxLines
     */
    public function create(
        OrderIdentifier $orderIdentifier,
        MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier,
        array $lines,
        Currency $currency,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $dueDate,
        ?Discount $discount,
        array $taxLines,
    ): Invoice {
        if ($lines === []) {
            throw new DomainException('Invoice must have at least one line.');
        }

        $this->assertCurrencies($lines, $currency);

        $subtotal = $this->calculateSubtotal($lines, $currency);
        $discountAmount = $discount?->amountFor($subtotal) ?? new Money(0, $currency);
        $net = $subtotal->subtract($discountAmount);

        $taxAmount = new Money(0, $currency);
        $total = $net;
        foreach ($taxLines as $taxLine) {
            $lineTax = $taxLine->taxAmountFor($net);
            $taxAmount = $taxAmount->add($lineTax);
            if (! $taxLine->isInclusive()) {
                $total = $total->add($lineTax);
            }
        }

        return new Invoice(
            new InvoiceIdentifier($this->generator->generate()),
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $lines,
            $subtotal,
            $discountAmount,
            $taxAmount,
            $total,
            $issuedAt,
            $dueDate,
            InvoiceStatus::ISSUED,
        );
    }

    /**
     * @param InvoiceLine[] $lines
     */
    private function calculateSubtotal(array $lines, Currency $currency): Money
    {
        $subtotal = new Money(0, $currency);
        foreach ($lines as $line) {
            $subtotal = $subtotal->add($line->lineTotal());
        }

        return $subtotal;
    }

    /**
     * @param InvoiceLine[] $lines
     */
    private function assertCurrencies(array $lines, Currency $currency): void
    {
        foreach ($lines as $line) {
            if ($line->unitPrice()->currency() !== $currency) {
                throw new DomainException('Invoice lines must share the same currency.');
            }
        }
    }
}
