<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class DelegatedIdentityCreated
{
    public function __construct(
        private DelegationIdentifier $delegationIdentifier,
        private IdentityIdentifier $delegatedIdentityIdentifier,
        private IdentityIdentifier $originalIdentityIdentifier,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function delegatedIdentityIdentifier(): IdentityIdentifier
    {
        return $this->delegatedIdentityIdentifier;
    }

    public function originalIdentityIdentifier(): IdentityIdentifier
    {
        return $this->originalIdentityIdentifier;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
