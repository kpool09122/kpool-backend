<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;

interface RegisterPaymentMethodInputPort
{
    public function operatorPrincipalIdentifier(): PrincipalIdentifier;

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier;

    public function externalPaymentMethodId(): ExternalPaymentMethodId;

    public function type(): PaymentMethodType;
}
