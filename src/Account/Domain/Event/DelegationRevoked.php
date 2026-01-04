<?php

declare(strict_types=1);

namespace Source\Account\Domain\Event;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class DelegationRevoked
{
    public function __construct(
        private DelegationIdentifier $delegationIdentifier,
        private DateTimeImmutable $revokedAt,
    ) {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function revokedAt(): DateTimeImmutable
    {
        return $this->revokedAt;
    }
}
