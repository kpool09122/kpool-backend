<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\Factory;

use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PolicyFactoryInterface
{
    /** @param Statement[] $statements */
    public function create(string $name, array $statements, ?AccountIdentifier $accountIdentifier = null): Policy;
}
