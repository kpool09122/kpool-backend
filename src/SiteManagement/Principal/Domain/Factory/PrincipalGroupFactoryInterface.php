<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Factory;

use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

interface PrincipalGroupFactoryInterface
{
    /** @param RoleIdentifier[] $roles */
    public function create(string $name, array $roles): PrincipalGroup;
}
