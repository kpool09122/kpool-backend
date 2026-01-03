<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

class CreateInvoice
{
    /**
     * @param array{
     *     order_id?: string,
     *     buyer_monetization_account_id?: string,
     *     currency?: Currency,
     *     subtotal?: int,
     *     discount_amount?: int,
     *     tax_amount?: int,
     *     total?: int,
     *     issued_at?: DateTimeImmutable,
     *     due_date?: DateTimeImmutable,
     *     status?: InvoiceStatus,
     *     tax_document_type?: ?string,
     *     tax_document_country?: ?string,
     *     tax_document_registration_number?: ?string,
     *     tax_document_issue_deadline?: ?DateTimeImmutable,
     *     tax_document_reason?: ?string,
     *     paid_at?: ?DateTimeImmutable,
     *     voided_at?: ?DateTimeImmutable,
     *     void_reason?: ?string,
     *     lines?: array<array{description: string, currency: Currency, unit_price: int, quantity: int}>
     * } $overrides
     */
    public static function create(
        InvoiceIdentifier $invoiceIdentifier,
        array $overrides = []
    ): void {
        $orderId = $overrides['order_id'] ?? StrTestHelper::generateUuid();
        $buyerMonetizationAccountId = $overrides['buyer_monetization_account_id'] ?? StrTestHelper::generateUuid();
        $currency = $overrides['currency'] ?? Currency::JPY;
        $subtotal = $overrides['subtotal'] ?? 1000;
        $discountAmount = $overrides['discount_amount'] ?? 0;
        $taxAmount = $overrides['tax_amount'] ?? 100;
        $total = $overrides['total'] ?? 1100;
        $issuedAt = $overrides['issued_at'] ?? new DateTimeImmutable();
        $dueDate = $overrides['due_date'] ?? new DateTimeImmutable('+30 days');
        $status = $overrides['status'] ?? InvoiceStatus::ISSUED;

        DB::table('invoices')->insert([
            'id' => (string) $invoiceIdentifier,
            'order_id' => $orderId,
            'buyer_monetization_account_id' => $buyerMonetizationAccountId,
            'currency' => $currency->value,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'issued_at' => $issuedAt,
            'due_date' => $dueDate,
            'status' => $status->value,
            'tax_document_type' => $overrides['tax_document_type'] ?? null,
            'tax_document_country' => $overrides['tax_document_country'] ?? null,
            'tax_document_registration_number' => $overrides['tax_document_registration_number'] ?? null,
            'tax_document_issue_deadline' => $overrides['tax_document_issue_deadline'] ?? null,
            'tax_document_reason' => $overrides['tax_document_reason'] ?? null,
            'paid_at' => $overrides['paid_at'] ?? null,
            'voided_at' => $overrides['voided_at'] ?? null,
            'void_reason' => $overrides['void_reason'] ?? null,
        ]);

        $lines = $overrides['lines'] ?? [
            [
                'description' => 'Test Item',
                'currency' => $currency,
                'unit_price' => $subtotal,
                'quantity' => 1,
            ],
        ];

        foreach ($lines as $line) {
            DB::table('invoice_lines')->insert([
                'invoice_id' => (string) $invoiceIdentifier,
                'description' => $line['description'],
                'currency' => $line['currency']->value,
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
            ]);
        }
    }

    /**
     * @param array{
     *     currency?: Currency,
     *     subtotal?: int,
     *     discount_amount?: int,
     *     tax_amount?: int,
     *     total?: int,
     *     issued_at?: DateTimeImmutable,
     *     due_date?: DateTimeImmutable,
     *     status?: InvoiceStatus,
     *     tax_document_type?: ?string,
     *     tax_document_country?: ?string,
     *     tax_document_registration_number?: ?string,
     *     tax_document_issue_deadline?: ?DateTimeImmutable,
     *     tax_document_reason?: ?string,
     *     paid_at?: ?DateTimeImmutable,
     *     voided_at?: ?DateTimeImmutable,
     *     void_reason?: ?string,
     *     lines?: array<array{description: string, currency: Currency, unit_price: int, quantity: int}>
     * } $overrides
     */
    public static function createWithOrderAndBuyerMonetizationAccount(
        InvoiceIdentifier $invoiceIdentifier,
        OrderIdentifier $orderIdentifier,
        MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier,
        array $overrides = []
    ): void {
        self::create($invoiceIdentifier, array_merge($overrides, [
            'order_id' => (string) $orderIdentifier,
            'buyer_monetization_account_id' => (string) $buyerMonetizationAccountIdentifier,
        ]));
    }
}
