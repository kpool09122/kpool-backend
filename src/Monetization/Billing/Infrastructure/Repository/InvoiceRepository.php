<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Infrastructure\Repository;

use Application\Models\Monetization\Invoice as InvoiceEloquent;
use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\Repository\InvoiceRepositoryInterface;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function findById(InvoiceIdentifier $invoiceIdentifier): ?Invoice
    {
        $eloquent = InvoiceEloquent::query()
            ->with('lines')
            ->where('id', (string) $invoiceIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(Invoice $invoice): void
    {
        $taxDocument = $invoice->taxDocument();

        $eloquent = InvoiceEloquent::query()->updateOrCreate(
            ['id' => (string) $invoice->invoiceIdentifier()],
            [
                'order_id' => (string) $invoice->orderIdentifier(),
                'buyer_monetization_account_id' => (string) $invoice->buyerMonetizationAccountIdentifier(),
                'currency' => $invoice->currency()->value,
                'subtotal' => $invoice->subtotal()->amount(),
                'discount_amount' => $invoice->discountAmount()->amount(),
                'tax_amount' => $invoice->taxAmount()->amount(),
                'total' => $invoice->total()->amount(),
                'issued_at' => $invoice->issuedAt(),
                'due_date' => $invoice->dueDate(),
                'status' => $invoice->status()->value,
                'tax_document_type' => $taxDocument?->type()->value,
                'tax_document_country' => $taxDocument?->country()->value,
                'tax_document_registration_number' => $taxDocument?->registrationNumber(),
                'tax_document_issue_deadline' => $taxDocument?->issueDeadline(),
                'tax_document_reason' => $taxDocument?->reason(),
                'paid_at' => $invoice->paidAt(),
                'voided_at' => $invoice->voidedAt(),
                'void_reason' => $invoice->voidReason(),
            ]
        );

        $eloquent->lines()->delete();

        foreach ($invoice->lines() as $line) {
            $eloquent->lines()->create([
                'description' => $line->description(),
                'currency' => $line->unitPrice()->currency()->value,
                'unit_price' => $line->unitPrice()->amount(),
                'quantity' => $line->quantity(),
            ]);
        }
    }

    private function toDomainEntity(InvoiceEloquent $eloquent): Invoice
    {
        $currency = Currency::from($eloquent->currency);

        $lines = $eloquent->lines->map(function ($line) {
            return new InvoiceLine(
                $line->description,
                new Money($line->unit_price, Currency::from($line->currency)),
                $line->quantity
            );
        })->all();

        $taxDocument = null;
        if ($eloquent->tax_document_type !== null) {
            $taxDocument = new TaxDocument(
                TaxDocumentType::from($eloquent->tax_document_type),
                CountryCode::from($eloquent->tax_document_country),
                $eloquent->tax_document_registration_number,
                DateTimeImmutable::createFromMutable($eloquent->tax_document_issue_deadline),
                $eloquent->tax_document_reason,
            );
        }

        return new Invoice(
            new InvoiceIdentifier($eloquent->id),
            new OrderIdentifier($eloquent->order_id),
            new MonetizationAccountIdentifier($eloquent->buyer_monetization_account_id),
            $lines,
            new Money($eloquent->subtotal, $currency),
            new Money($eloquent->discount_amount, $currency),
            new Money($eloquent->tax_amount, $currency),
            new Money($eloquent->total, $currency),
            DateTimeImmutable::createFromMutable($eloquent->issued_at),
            DateTimeImmutable::createFromMutable($eloquent->due_date),
            InvoiceStatus::from($eloquent->status),
            $taxDocument,
            $eloquent->paid_at !== null ? DateTimeImmutable::createFromMutable($eloquent->paid_at) : null,
            $eloquent->voided_at !== null ? DateTimeImmutable::createFromMutable($eloquent->voided_at) : null,
            $eloquent->void_reason,
        );
    }
}
