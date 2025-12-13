<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Service;

use DateTimeImmutable;
use DomainException;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;

class PaymentMatcherService implements PaymentMatcherServiceInterface
{
    public function match(Invoice $invoice, Payment $payment, DateTimeImmutable $paidAt): void
    {
        if ((string) $invoice->orderIdentifier() !== (string) $payment->orderIdentifier()) {
            throw new DomainException('Invoice and Payment are not for the same order.');
        }
        if ($payment->status() !== PaymentStatus::CAPTURED) {
            throw new DomainException('Payment must be captured before matching to invoice.');
        }
        if (! $payment->money()->isSameCurrency($invoice->total())) {
            throw new DomainException('Payment currency does not match invoice.');
        }
        if ($payment->money()->amount() !== $invoice->total()->amount()) {
            throw new DomainException('Payment amount does not match invoice total.');
        }

        $invoice->recordPayment($payment->money(), $paidAt);
    }
}
