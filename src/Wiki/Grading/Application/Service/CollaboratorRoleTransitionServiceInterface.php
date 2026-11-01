<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\Service;

use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface CollaboratorRoleTransitionServiceInterface
{
    public function promote(PrincipalIdentifier $principalIdentifier, Role $seniorRole): bool;

    public function demote(PrincipalIdentifier $principalIdentifier, Role $seniorRole): bool;
}
