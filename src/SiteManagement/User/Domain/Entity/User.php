<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Domain\Entity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

class User
{
    public function __construct(
        private readonly UserIdentifier $userIdentifier,
        private readonly IdentityIdentifier $identityIdentifier,
        private Role $role,
    ) {
    }

    public function userIdentifier(): UserIdentifier
    {
        return $this->userIdentifier;
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

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }
}
