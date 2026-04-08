<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Factory;

use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

interface RegisteredPaymentMethodFactoryInterface
{
    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        PaymentMethodId $paymentMethodId,
        PaymentMethodType $type,
    ): RegisteredPaymentMethod;
}
