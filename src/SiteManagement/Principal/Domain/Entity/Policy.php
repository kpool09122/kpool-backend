<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Entity;

use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Statement;

class Policy
{
    /** @param Statement[] $statements */
    public function __construct(private readonly PolicyIdentifier $policyIdentifier, private readonly string $name, private readonly array $statements)
    {
    }

    public function policyIdentifier(): PolicyIdentifier
    {
        return $this->policyIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return Statement[] */
    public function statements(): array
    {
        return $this->statements;
    }
}
