<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

interface RegisterPaymentMethodInputPort
{
    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier;

    public function paymentMethodId(): PaymentMethodId;

    public function type(): PaymentMethodType;
}
