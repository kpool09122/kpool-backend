<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
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
        ?AccountIdentifier $accountIdentifier,
    ): Role;
}
