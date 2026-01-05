<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class DelegatedIdentityDeleted
{
    public function __construct(
        private DelegationIdentifier $delegationIdentifier,
        private DateTimeImmutable $deletedAt,
    ) {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function deletedAt(): DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
