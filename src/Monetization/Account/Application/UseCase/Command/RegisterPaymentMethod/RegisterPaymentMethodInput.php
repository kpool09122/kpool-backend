<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RegisterPaymentMethodInput implements RegisterPaymentMethodInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private SetupIntentIdentifier $setupIntentIdentifier,
        private PrincipalIdentifier $operatorPrincipalIdentifier,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function setupIntentIdentifier(): SetupIntentIdentifier
    {
        return $this->setupIntentIdentifier;
    }

    public function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier;
    }
}
