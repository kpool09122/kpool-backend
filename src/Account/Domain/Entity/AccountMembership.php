<?php

declare(strict_types=1);

namespace Source\Account\Domain\Entity;

use Source\Account\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\UserIdentifier;

readonly class AccountMembership
{
    public function __construct(
        private UserIdentifier $userIdentifier,
        private AccountRole $role,
    ) {
    }

    public function userIdentifier(): UserIdentifier
    {
        return $this->userIdentifier;
    }

    public function role(): AccountRole
    {
        return $this->role;
    }
}
