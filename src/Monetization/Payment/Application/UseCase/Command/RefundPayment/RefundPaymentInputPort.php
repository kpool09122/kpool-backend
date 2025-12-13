<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Shared\Domain\ValueObject\Money;

interface RefundPaymentInputPort
{
    public function paymentIdentifier(): PaymentIdentifier;

    public function refundAmount(): Money;

    public function reason(): string;
}
