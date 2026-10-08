<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Entity;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class Role
{
    public const string GENERAL = '69200000-0000-7000-8000-000000000001';
    public const string ADMINISTRATOR = '69200000-0000-7000-8000-000000000002';

    /** @param PolicyIdentifier[] $policies */
    public function __construct(
        private readonly RoleIdentifier $roleIdentifier,
        private readonly string $name,
        private readonly array $policies,
        private readonly ?AccountIdentifier $accountIdentifier = null,
    ) {
    }

    public function roleIdentifier(): RoleIdentifier
    {
        return $this->roleIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return PolicyIdentifier[] */
    public function policies(): array
    {
        return $this->policies;
    }

    public function isSystemRole(): bool
    {
        return $this->accountIdentifier === null;
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
