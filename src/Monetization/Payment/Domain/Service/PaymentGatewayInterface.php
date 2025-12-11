<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Service;

use Source\Monetization\Payment\Domain\Entity\Payment;

interface PaymentGatewayInterface
{
    public function authorize(Payment $payment): void;
}
