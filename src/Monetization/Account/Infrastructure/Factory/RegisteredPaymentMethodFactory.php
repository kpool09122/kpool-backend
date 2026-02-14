<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Factory;

use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\Factory\RegisteredPaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class RegisteredPaymentMethodFactory implements RegisteredPaymentMethodFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        PaymentMethodId $paymentMethodId,
        PaymentMethodType $type,
    ): RegisteredPaymentMethod {
        return new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier($this->uuidGenerator->generate()),
            $monetizationAccountIdentifier,
            $paymentMethodId,
            $type,
        );
    }
}
