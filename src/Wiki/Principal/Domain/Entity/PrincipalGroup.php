<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

class PrincipalGroup
{
    public function __construct(
        private readonly PrincipalGroupIdentifier $principalGroupIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly string $name,
        private readonly bool $isDefault,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
