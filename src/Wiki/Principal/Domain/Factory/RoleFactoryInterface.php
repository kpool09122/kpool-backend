<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

interface RoleFactoryInterface
{
    /**
     * @param PolicyIdentifier[] $policies
     */
    public function create(
        string $name,
        array $policies,
        bool $isSystemRole,
    ): Role;
}
