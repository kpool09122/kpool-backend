<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Domain\Factory;

use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountDelegationFactoryInterface
{
    public function create(Affiliation $affiliation, AccountIdentifier $requestedByAccountIdentifier): AccountDelegation;
}
