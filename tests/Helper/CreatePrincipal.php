<?php

declare(strict_types=1);

namespace Tests\Helper;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

trait CreatePrincipal
{
    /**
     * @param string[] $groupIds
     * @param string[] $talentIds
     */
    protected function createPrincipal(
        ?PrincipalIdentifier $principalIdentifier = null,
        ?IdentityIdentifier  $identityIdentifier = null,
        Role                 $role = Role::ADMINISTRATOR,
        ?string              $agencyId = null,
        array                $groupIds = [],
        array                $talentIds = [],
    ): Principal {
        return new Principal(
            $principalIdentifier ?? new PrincipalIdentifier(StrTestHelper::generateUlid()),
            $identityIdentifier ?? new IdentityIdentifier(StrTestHelper::generateUlid()),
            $role,
            $agencyId,
            $groupIds,
            $talentIds,
        );
    }
}
