<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Entity;

use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class Role
{
    /** @param PolicyIdentifier[] $policies */
    public function __construct(private readonly RoleIdentifier $roleIdentifier, private readonly string $name, private readonly array $policies)
    {
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
}
