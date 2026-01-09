<?php

declare(strict_types=1);

namespace Source\Account\Domain\Factory;

use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\ValueObject\AccountRole;
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
