<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

class AccountDelegation
{
    public function __construct(
        private readonly DelegationIdentifier $delegationIdentifier,
        private readonly AffiliationIdentifier $affiliationIdentifier,
        private readonly AccountIdentifier $delegateAccountIdentifier,
        private readonly AccountIdentifier $delegatorAccountIdentifier,
        private readonly AccountIdentifier $requestedByAccountIdentifier,
        private DelegationStatus $status,
        private readonly DelegationDirection $direction,
        private readonly DateTimeImmutable $requestedAt,
        private ?DateTimeImmutable $approvedAt,
        private readonly ?DateTimeImmutable $revokedAt,
    ) {
    }

    public function delegationIdentifier(): DelegationIdentifier
    {
        return $this->delegationIdentifier;
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function delegateAccountIdentifier(): AccountIdentifier
    {
        return $this->delegateAccountIdentifier;
    }

    public function delegatorAccountIdentifier(): AccountIdentifier
    {
        return $this->delegatorAccountIdentifier;
    }

    public function requestedByAccountIdentifier(): AccountIdentifier
    {
        return $this->requestedByAccountIdentifier;
    }

    public function status(): DelegationStatus
    {
        return $this->status;
    }

    public function direction(): DelegationDirection
    {
        return $this->direction;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function approve(): void
    {
        if (! $this->isPending()) {
            throw new DomainException('Only pending delegations can be approved.');
        }
        $this->status = DelegationStatus::APPROVED;
        $this->approvedAt = new DateTimeImmutable();
    }
}
