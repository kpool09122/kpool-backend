<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Entity;

use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class Policy
{
    /** @param Statement[] $statements */
    public function __construct(
        private PolicyIdentifier   $policyIdentifier,
        private string             $name,
        private array              $statements,
        private ?AccountIdentifier $accountIdentifier,
    ) {
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

    public function isSystemPolicy(): bool
    {
        return $this->accountIdentifier === null;
    }

    public function accountIdentifier(): ?AccountIdentifier
    {
        return $this->accountIdentifier;
    }
}
