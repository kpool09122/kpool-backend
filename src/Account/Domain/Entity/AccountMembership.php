<?php

declare(strict_types=1);

namespace Source\Account\Domain\Entity;

use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\AccountUserIdentifier;

readonly class AccountMembership
{
    public function __construct(
        private AccountUserIdentifier $userIdentifier,
        private AccountRole $role,
    ) {
    }

    public function userIdentifier(): AccountUserIdentifier
    {
        return $this->userIdentifier;
    }

    public function role(): AccountRole
    {
        return $this->role;
    }
}
