<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Factory;

use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface DelegationFactoryInterface
{
    public function create(
        AffiliationIdentifier $affiliationIdentifier,
        IdentityIdentifier $delegateIdentifier,
        IdentityIdentifier $delegatorIdentifier,
    ): Delegation;
}
