<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Shared\Domain\ValueObject\Money;

interface PaymentFactoryInterface
{
    public function create(
        Money             $money,
        PaymentMethod     $paymentMethod,
        DateTimeImmutable $createdAt,
    ): Payment;
}
