<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Factory;

use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Factory\PaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class PaymentMethodFactory implements PaymentMethodFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        ExternalPaymentMethodId $externalPaymentMethodId,
        PaymentMethodType $type,
    ): PaymentMethod {
        return new PaymentMethod(
            new PaymentMethodIdentifier($this->uuidGenerator->generate()),
            $monetizationAccountIdentifier,
            $externalPaymentMethodId,
            $type,
        );
    }
}
