<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RegisterPaymentMethodInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function setupIntentIdentifier(): SetupIntentIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;
}
