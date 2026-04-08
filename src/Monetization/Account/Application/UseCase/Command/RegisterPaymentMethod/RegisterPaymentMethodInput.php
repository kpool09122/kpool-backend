<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

readonly class RegisterPaymentMethodInput implements RegisterPaymentMethodInputPort
{
    public function __construct(
        private MonetizationAccountIdentifier $monetizationAccountIdentifier,
        private PaymentMethodId $paymentMethodId,
        private PaymentMethodType $type,
    ) {
    }

    public function monetizationAccountIdentifier(): MonetizationAccountIdentifier
    {
        return $this->monetizationAccountIdentifier;
    }

    public function paymentMethodId(): PaymentMethodId
    {
        return $this->paymentMethodId;
    }

    public function type(): PaymentMethodType
    {
        return $this->type;
    }
}
