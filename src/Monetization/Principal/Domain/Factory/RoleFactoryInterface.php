<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\Factory;

use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface RoleFactoryInterface
{
    /** @param PolicyIdentifier[] $policies */
    public function create(string $name, array $policies, ?AccountIdentifier $accountIdentifier = null): Role;
}
