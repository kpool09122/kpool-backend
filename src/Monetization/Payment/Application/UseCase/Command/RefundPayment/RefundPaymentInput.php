<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Shared\Domain\ValueObject\Money;

readonly class RefundPaymentInput implements RefundPaymentInputPort
{
    public function __construct(
        private PaymentIdentifier $paymentIdentifier,
        private Money $refundAmount,
        private string $reason,
    ) {
    }

    public function paymentIdentifier(): PaymentIdentifier
    {
        return $this->paymentIdentifier;
    }

    public function refundAmount(): Money
    {
        return $this->refundAmount;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
