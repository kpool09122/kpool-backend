<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Factory;

use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PrincipalGroupFactoryInterface
{
    public function create(
        AccountIdentifier $accountIdentifier,
        string $name,
        bool $isDefault,
    ): PrincipalGroup;
}
