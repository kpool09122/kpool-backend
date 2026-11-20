<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\Factory;

use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PrincipalGroupFactoryInterface
{
    /** @param RoleIdentifier[] $roles */
    public function create(string $name, array $roles, AccountIdentifier $accountIdentifier, bool $isDefault = false): PrincipalGroup;
}
