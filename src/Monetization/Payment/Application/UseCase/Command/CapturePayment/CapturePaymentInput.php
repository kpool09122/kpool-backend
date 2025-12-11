<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\CapturePayment;

use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;

readonly class CapturePaymentInput implements CapturePaymentInputPort
{
    public function __construct(
        private PaymentIdentifier $paymentIdentifier,
    ) {
    }

    public function paymentIdentifier(): PaymentIdentifier
    {
        return $this->paymentIdentifier;
    }
}
