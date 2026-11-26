<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

interface PaymentFactoryInterface
{
    public function create(
        OrderIdentifier   $orderIdentifier,
        MonetizationAccountIdentifier $buyerMonetizationAccountIdentifier,
        Money             $money,
        PaymentMethodIdentifier     $paymentMethodIdentifier,
        DateTimeImmutable $createdAt,
    ): Payment;
}
