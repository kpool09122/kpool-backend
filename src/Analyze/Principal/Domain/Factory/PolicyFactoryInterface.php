<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Factory;

use Source\Analyze\Principal\Domain\Entity\Policy;
use Source\Analyze\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PolicyFactoryInterface
{
    /** @param Statement[] $statements */
    public function create(string $name, array $statements, ?AccountIdentifier $accountIdentifier = null): Policy;
}
