<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class RegisterPaymentMethodInput implements RegisterPaymentMethodInputPort
{
    public function __construct(
        private MonetizationAccountIdentifier $monetizationAccountIdentifier,
        private ExternalPaymentMethodId $externalPaymentMethodId,
        private PaymentMethodType $type,
        private PrincipalIdentifier $operatorPrincipalIdentifier,
    ) {
    }

    public function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier;
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function externalPaymentMethodId(): ExternalPaymentMethodId
    {
        return $this->externalPaymentMethodId;
    }

    public function type(): PaymentMethodType
    {
        return $this->type;
    }
}
