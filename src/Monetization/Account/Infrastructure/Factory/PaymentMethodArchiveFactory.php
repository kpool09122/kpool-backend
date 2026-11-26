<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;
use Source\Monetization\Account\Domain\Factory\PaymentMethodArchiveFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodArchiveIdentifier;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class PaymentMethodArchiveFactory implements PaymentMethodArchiveFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(PaymentMethod $paymentMethod, DateTimeImmutable $archivedAt): PaymentMethodArchive
    {
        return new PaymentMethodArchive(
            new PaymentMethodArchiveIdentifier($this->uuidGenerator->generate()),
            $paymentMethod->paymentMethodIdentifier(),
            $paymentMethod->monetizationAccountIdentifier(),
            $paymentMethod->externalPaymentMethodId(),
            $paymentMethod->type(),
            $paymentMethod->meta()?->brand(),
            $paymentMethod->meta()?->last4(),
            $archivedAt
        );
    }
}
