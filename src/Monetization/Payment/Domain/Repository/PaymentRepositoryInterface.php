<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Repository;

use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;

interface PaymentRepositoryInterface
{
    public function findById(PaymentIdentifier $paymentIdentifier): ?Payment;

    public function save(Payment $payment): void;
}
