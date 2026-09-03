<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Factory;

use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountDelegationFactoryInterface
{
    public function create(Affiliation $affiliation, AccountIdentifier $requestedByAccountIdentifier): AccountDelegation;
}
