<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Repository;

use Source\Monetization\Payment\Domain\Entity\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
}
