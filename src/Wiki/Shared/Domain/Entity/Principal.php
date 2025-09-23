<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Entity;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

class Principal
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param Role $role
     * @param string|null $agencyId
     * @param string[] $groupIds
     * @param string|null $memberId
     */
    public function __construct(
        private readonly PrincipalIdentifier $principalIdentifier,
        private Role                         $role,
        private readonly ?string             $agencyId,
        private readonly array               $groupIds,
        private readonly ?string             $memberId,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
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

    public function memberId(): ?string
    {
        return $this->memberId;
    }
}
