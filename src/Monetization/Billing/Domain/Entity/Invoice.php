<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\UserIdentifier;

class Invoice
{
    /**
     * @param InvoiceLine[] $lines
     */
    public function __construct(
        private readonly InvoiceIdentifier $invoiceIdentifier,
        private readonly UserIdentifier $customerIdentifier,
        private readonly array $lines,
        private readonly Money $subtotal,
        private readonly Money $discountAmount,
        private readonly Money $taxAmount,
        private readonly Money $total,
        private readonly DateTimeImmutable $issuedAt,
        private readonly DateTimeImmutable $dueDate,
        private InvoiceStatus $status,
        private ?TaxDocument  $taxDocument = null,
        private ?DateTimeImmutable $paidAt = null,
        private ?DateTimeImmutable $voidedAt = null,
        private ?string $voidReason = null,
    ) {
        $this->assertAmounts($lines, $subtotal, $discountAmount, $taxAmount, $total);
    }

    public function invoiceIdentifier(): InvoiceIdentifier
    {
        return $this->invoiceIdentifier;
    }

    public function customerIdentifier(): UserIdentifier
    {
        return $this->customerIdentifier;
    }

    /**
     * @return InvoiceLine[]
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function currency(): Currency
    {
        return $this->total->currency();
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function discountAmount(): Money
    {
        return $this->discountAmount;
    }

    public function taxAmount(): Money
    {
        return $this->taxAmount;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function issuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function dueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function status(): InvoiceStatus
    {
        return $this->status;
    }

    public function taxDocument(): ?TaxDocument
    {
        return $this->taxDocument;
    }

    public function setTaxDocument(TaxDocument $taxDocument): void
    {
        $this->taxDocument = $taxDocument;
    }

    public function paidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function voidedAt(): ?DateTimeImmutable
    {
        return $this->voidedAt;
    }

    public function voidReason(): ?string
    {
        return $this->voidReason;
    }

    /**
     * @param InvoiceLine[] $lines
     */
    private function assertAmounts(
        array $lines,
        Money $subtotal,
        Money $discountAmount,
        Money $taxAmount,
        Money $total
    ): void {
        if ($lines === []) {
            throw new DomainException('Invoice must have at least one line.');
        }

        $currency = $subtotal->currency();
        $calculatedSubtotal = new Money(0, $currency);
        foreach ($lines as $line) {
            if ($line->unitPrice()->currency() !== $currency) {
                throw new DomainException('Invoice lines must use the same currency as the invoice.');
            }
            $calculatedSubtotal = $calculatedSubtotal->add($line->lineTotal());
        }

        if ($calculatedSubtotal->amount() !== $subtotal->amount()) {
            throw new DomainException('Subtotal does not match sum of invoice lines.');
        }

        if (! $discountAmount->isSameCurrency($subtotal)) {
            throw new DomainException('Discount currency must match invoice currency.');
        }
        if ($discountAmount->amount() > $subtotal->amount()) {
            throw new DomainException('Discount exceeds subtotal.');
        }

        $net = $subtotal->subtract($discountAmount);

        if (! $taxAmount->isSameCurrency($subtotal)) {
            throw new DomainException('Tax currency must match invoice currency.');
        }
        if (! $total->isSameCurrency($subtotal)) {
            throw new DomainException('Total currency must match invoice currency.');
        }

        $totalAmount = $total->amount();
        $minTotal = $net->amount();
        $maxTotal = $net->amount() + $taxAmount->amount();
        if ($totalAmount < $minTotal || $totalAmount > $maxTotal) {
            throw new DomainException('Total must be between net amount and net plus tax.');
        }
    }

    public function recordPayment(Money $paidAmount, DateTimeImmutable $paidAt): void
    {
        if ($this->status !== InvoiceStatus::ISSUED) {
            throw new DomainException('Invoice is not payable.');
        }
        if (! $paidAmount->isSameCurrency($this->total)) {
            throw new DomainException('Payment currency must match invoice currency.');
        }
        if ($paidAmount->amount() !== $this->total->amount()) {
            throw new DomainException('Payment amount must match invoice total.');
        }

        $this->paidAt = $paidAt;
        $this->status = InvoiceStatus::PAID;
    }

    public function void(string $reason, DateTimeImmutable $voidedAt): void
    {
        if ($this->status !== InvoiceStatus::ISSUED) {
            throw new DomainException('Only issued invoices can be voided.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Void reason must not be empty.');
        }

        $this->voidReason = $reason;
        $this->voidedAt = $voidedAt;
        $this->status = InvoiceStatus::VOID;
    }
}
