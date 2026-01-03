<?php

declare(strict_types=1);

namespace Source\Account\Domain\Entity;

use Source\Account\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountMembership
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private AccountRole        $role,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function role(): AccountRole
    {
        return $this->role;
    }
}
