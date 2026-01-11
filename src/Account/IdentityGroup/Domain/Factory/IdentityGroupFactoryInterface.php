<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Domain\Factory;

use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface IdentityGroupFactoryInterface
{
    public function create(
        AccountIdentifier $accountIdentifier,
        string $name,
        AccountRole $role,
        bool $isDefault,
    ): IdentityGroup;
}
