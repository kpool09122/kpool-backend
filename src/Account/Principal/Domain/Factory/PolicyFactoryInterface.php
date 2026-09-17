<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Factory;

use Source\Account\Principal\Domain\Entity\Policy;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PolicyFactoryInterface
{
    /** @param \Source\Account\Principal\Domain\ValueObject\Statement[] $statements */
    public function create(string $name, array $statements, AccountIdentifier $accountIdentifier): Policy;
}
