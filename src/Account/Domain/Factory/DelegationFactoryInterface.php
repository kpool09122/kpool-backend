<?php

declare(strict_types=1);

namespace Source\Account\Domain\Factory;

use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface DelegationFactoryInterface
{
    public function create(
        AffiliationIdentifier $affiliationIdentifier,
        IdentityIdentifier $delegateIdentifier,
        IdentityIdentifier $delegatorIdentifier,
    ): OperationDelegation;
}
