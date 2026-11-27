<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PrepareCardRegistrationInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;
}
