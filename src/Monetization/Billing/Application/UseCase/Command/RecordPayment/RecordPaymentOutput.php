<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Application\UseCase\Command\RecordPayment;

use DateTimeInterface;
use Source\Monetization\Billing\Domain\Entity\Invoice;

class RecordPaymentOutput implements RecordPaymentOutputPort
{
    private ?Invoice $invoice = null;

    public function setInvoice(Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->invoice === null) {
            return [];
        }

        $invoice = $this->invoice;

        return [
            'invoiceIdentifier' => (string) $invoice->invoiceIdentifier(),
            'orderIdentifier' => (string) $invoice->orderIdentifier(),
            'buyerMonetizationAccountIdentifier' => (string) $invoice->buyerMonetizationAccountIdentifier(),
            'subtotal' => $invoice->subtotal()->amount(),
            'discountAmount' => $invoice->discountAmount()->amount(),
            'taxAmount' => $invoice->taxAmount()->amount(),
            'total' => $invoice->total()->amount(),
            'currency' => $invoice->currency()->value,
            'status' => $invoice->status()->value,
            'issuedAt' => $invoice->issuedAt()->format(DateTimeInterface::ATOM),
            'dueDate' => $invoice->dueDate()->format(DateTimeInterface::ATOM),
            'paidAt' => $invoice->paidAt()?->format(DateTimeInterface::ATOM),
        ];
    }
}
