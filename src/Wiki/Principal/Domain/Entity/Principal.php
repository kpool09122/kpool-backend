<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class Principal
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param IdentityIdentifier $identityIdentifier
     * @param Role $role
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string[] $talentIds
     */
    public function __construct(
        private readonly PrincipalIdentifier $principalIdentifier,
        private readonly IdentityIdentifier  $identityIdentifier,
        private Role                         $role,
        private readonly ?string             $agencyId,
        private readonly array               $groupIds,
        private readonly array               $talentIds,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }

    public function agencyId(): ?string
    {
        return $this->agencyId;
    }

    /**
     * @return string[]
     */
    public function groupIds(): array
    {
        return $this->groupIds;
    }

    /**
     * @return string[]
     */
    public function talentIds(): array
    {
        return $this->talentIds;
    }
}
