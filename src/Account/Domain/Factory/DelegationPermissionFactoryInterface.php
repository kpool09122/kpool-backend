<?php

declare(strict_types=1);

namespace Source\Account\Domain\Factory;

use Source\Account\Domain\Entity\DelegationPermission;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface DelegationPermissionFactoryInterface
{
    public function create(
        IdentityGroupIdentifier $identityGroupIdentifier,
        AccountIdentifier $targetAccountIdentifier,
        AffiliationIdentifier $affiliationIdentifier,
    ): DelegationPermission;
}
