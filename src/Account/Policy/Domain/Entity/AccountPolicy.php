<?php

declare(strict_types=1);

namespace Source\Account\Policy\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Policy\Domain\ValueObject\AccountPolicyIdentifier;
use Source\Account\Policy\Domain\ValueObject\Statement;

class AccountPolicy
{
    /**
     * @param Statement[] $statements
     */
    public function __construct(
        private readonly AccountPolicyIdentifier $accountPolicyIdentifier,
        private readonly string $name,
        private readonly array $statements,
        private readonly bool $isSystemPolicy,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function accountPolicyIdentifier(): AccountPolicyIdentifier
    {
        return $this->accountPolicyIdentifier;
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
        return $this->isSystemPolicy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
