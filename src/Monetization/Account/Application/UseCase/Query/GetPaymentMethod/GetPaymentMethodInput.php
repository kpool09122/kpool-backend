<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class GetPaymentMethodInput implements GetPaymentMethodInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private PaymentMethodIdentifier $paymentMethodIdentifier,
        private PrincipalIdentifier $operatorPrincipalIdentifier,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function paymentMethodIdentifier(): PaymentMethodIdentifier
    {
        return $this->paymentMethodIdentifier;
    }

    public function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier;
    }
}
