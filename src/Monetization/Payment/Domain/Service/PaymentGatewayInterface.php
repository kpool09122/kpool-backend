<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Service;

use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Shared\Domain\ValueObject\Money;

interface PaymentGatewayInterface
{
    public function authorize(Payment $payment): void;

    public function capture(Payment $payment): void;

    public function refund(Payment $payment, Money $amount, string $reason): void;
}
