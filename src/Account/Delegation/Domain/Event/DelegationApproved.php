<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Event;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class DelegationApproved
{
    public function __construct(
        private DelegationIdentifier $delegationIdentifier,
        private IdentityIdentifier $delegateIdentifier,
        private IdentityIdentifier $delegatorIdentifier,
        private DateTimeImmutable $approvedAt,
    ) {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function delegateIdentifier(): IdentityIdentifier
    {
        return $this->delegateIdentifier;
    }

    public function delegatorIdentifier(): IdentityIdentifier
    {
        return $this->delegatorIdentifier;
    }

    public function approvedAt(): DateTimeImmutable
    {
        return $this->approvedAt;
    }
}
