<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

interface PaymentFactoryInterface
{
    public function create(
        OrderIdentifier   $orderIdentifier,
        MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier,
        Money             $money,
        PaymentMethod     $paymentMethod,
        DateTimeImmutable $createdAt,
    ): Payment;
}
