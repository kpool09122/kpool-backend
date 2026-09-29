<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Factory;

use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RoleFactoryInterface
{
    /** @param PolicyIdentifier[] $policies */
    public function create(string $name, array $policies, AccountIdentifier $accountIdentifier): Role;
}
