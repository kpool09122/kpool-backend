<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Domain\Factory;

use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface DelegationPermissionFactoryInterface
{
    public function create(
        IdentityGroupIdentifier $identityGroupIdentifier,
        AccountIdentifier $targetAccountIdentifier,
        AffiliationIdentifier $affiliationIdentifier,
    ): DelegationPermission;
}
