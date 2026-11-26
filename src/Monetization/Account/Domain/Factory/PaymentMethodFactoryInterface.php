<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Factory;

use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

interface PaymentMethodFactoryInterface
{
    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        ExternalPaymentMethodId $externalPaymentMethodId,
        PaymentMethodType $type,
    ): PaymentMethod;
}
