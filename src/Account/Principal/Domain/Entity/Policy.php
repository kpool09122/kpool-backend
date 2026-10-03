<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class Policy
{
    /**
     * @param Statement[] $statements
     */
    public function __construct(
        private readonly PolicyIdentifier $policyIdentifier,
        private readonly string $name,
        private readonly array $statements,
        private readonly ?AccountIdentifier $accountIdentifier,
        private readonly DateTimeImmutable $createdAt,
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

    /**
     * @return Statement[]
     */
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

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
