<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class AccountContext
{
    public function __construct(
        public AccountIdentifier $accountIdentifier,
        public AccountRole $role,
    ) {
    }

    public function isOwner(): bool
    {
        return $this->role === AccountRole::OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->role === AccountRole::ADMIN;
    }
}
