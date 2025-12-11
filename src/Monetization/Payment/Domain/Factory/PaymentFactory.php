<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;

readonly class PaymentFactory implements PaymentFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $generator,
    ) {
    }

    public function create(
        OrderIdentifier   $orderIdentifier,
        Money             $money,
        PaymentMethod     $paymentMethod,
        DateTimeImmutable $createdAt,
    ): Payment {
        return new Payment(
            new PaymentIdentifier($this->generator->generate()),
            $orderIdentifier,
            $money,
            $paymentMethod,
            $createdAt,
            PaymentStatus::PENDING,
            null,
            null,
            null,
            null,
            new Money(0, $money->currency()),
            null
        );
    }
}
