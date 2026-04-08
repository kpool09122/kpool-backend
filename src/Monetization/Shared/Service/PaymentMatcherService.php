<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\Service;

use DateTimeImmutable;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Shared\Exception\PaymentAmountMismatchForMatchingException;
use Source\Monetization\Shared\Exception\PaymentCurrencyMismatchForMatchingException;
use Source\Monetization\Shared\Exception\PaymentNotCapturedForMatchingException;
use Source\Monetization\Shared\Exception\PaymentOrderMismatchException;

class PaymentMatcherService implements PaymentMatcherServiceInterface
{
    public function match(Invoice $invoice, Payment $payment, DateTimeImmutable $paidAt): void
    {
        if ((string) $invoice->orderIdentifier() !== (string) $payment->orderIdentifier()) {
            throw new PaymentOrderMismatchException();
        }
        if ($payment->status() !== PaymentStatus::CAPTURED) {
            throw new PaymentNotCapturedForMatchingException();
        }
        if (! $payment->money()->isSameCurrency($invoice->total())) {
            throw new PaymentCurrencyMismatchForMatchingException();
        }
        if ($payment->money()->amount() !== $invoice->total()->amount()) {
            throw new PaymentAmountMismatchForMatchingException();
        }

        $invoice->recordPayment($payment->money(), $paidAt);
    }
}
