<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PrepareCardRegistrationInput implements PrepareCardRegistrationInputPort
{
    public function __construct(private AccountIdentifier $accountIdentifier, private PrincipalIdentifier $operatorPrincipalIdentifier)
    {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier;
    }
}
