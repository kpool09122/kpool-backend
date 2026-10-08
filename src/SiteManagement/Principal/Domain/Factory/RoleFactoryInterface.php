<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Factory;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;

interface RoleFactoryInterface
{
    /** @param PolicyIdentifier[] $policies */
    public function create(string $name, array $policies, ?AccountIdentifier $accountIdentifier = null): Role;
}
