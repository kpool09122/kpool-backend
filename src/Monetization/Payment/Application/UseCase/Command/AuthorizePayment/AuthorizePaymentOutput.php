<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeInterface;
use LogicException;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Payment\Domain\Entity\Payment;

class AuthorizePaymentOutput implements AuthorizePaymentOutputPort
{
    private ?Payment $payment = null;

    private ?PaymentMethod $paymentMethod = null;

    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function setPayment(Payment $payment): void
    {
        $this->payment = $payment;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->payment === null) {
            return [
                'paymentId' => null,
                'orderIdentifier' => null,
                'buyerMonetizationAccountIdentifier' => null,
                'amount' => null,
                'currency' => null,
                'paymentMethodIdentifier' => null,
                'paymentMethodType' => null,
                'paymentMethodBrand' => null,
                'paymentMethodLast4' => null,
                'status' => null,
                'createdAt' => null,
                'authorizedAt' => null,
                'capturedAt' => null,
                'failedAt' => null,
                'failureReason' => null,
                'refundedAmount' => null,
                'refundedCurrency' => null,
                'lastRefundedAt' => null,
                'lastRefundReason' => null,
            ];
        }

        $paymentMethod = $this->paymentMethod ?? throw new LogicException('Payment method must be set before serializing a payment.');

        return [
            'paymentId' => (string) $this->payment->paymentId(),
            'orderIdentifier' => (string) $this->payment->orderIdentifier(),
            'buyerMonetizationAccountIdentifier' => (string) $this->payment->buyerMonetizationAccountIdentifier(),
            'amount' => $this->payment->money()->amount(),
            'currency' => $this->payment->money()->currency()->value,
            'paymentMethodIdentifier' => (string) $this->payment->paymentMethodIdentifier(),
            'paymentMethodType' => $paymentMethod->type()->value,
            'paymentMethodBrand' => $paymentMethod->meta()?->brand(),
            'paymentMethodLast4' => $paymentMethod->meta()?->last4(),
            'status' => $this->payment->status()->value,
            'createdAt' => $this->payment->createdAt()->format(DateTimeInterface::ATOM),
            'authorizedAt' => $this->payment->authorizedAt()?->format(DateTimeInterface::ATOM),
            'capturedAt' => $this->payment->capturedAt()?->format(DateTimeInterface::ATOM),
            'failedAt' => $this->payment->failedAt()?->format(DateTimeInterface::ATOM),
            'failureReason' => $this->payment->failureReason(),
            'refundedAmount' => $this->payment->refundedMoney()->amount(),
            'refundedCurrency' => $this->payment->refundedMoney()->currency()->value,
            'lastRefundedAt' => $this->payment->lastRefundedAt()?->format(DateTimeInterface::ATOM),
            'lastRefundReason' => $this->payment->lastRefundReason(),
        ];
    }
}
