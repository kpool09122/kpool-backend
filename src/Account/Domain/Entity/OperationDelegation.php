<?php

declare(strict_types=1);

namespace Source\Account\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationDirection;
use Source\Account\Domain\ValueObject\DelegationStatus;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class OperationDelegation
{
    public function __construct(
        private readonly DelegationIdentifier $delegationIdentifier,
        private readonly AffiliationIdentifier $affiliationIdentifier,
        private readonly IdentityIdentifier $delegateIdentifier,
        private readonly IdentityIdentifier $delegatorIdentifier,
        private DelegationStatus $status,
        private readonly DelegationDirection $direction,
        private readonly DateTimeImmutable $requestedAt,
        private ?DateTimeImmutable $approvedAt,
        private ?DateTimeImmutable $revokedAt,
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

    public function delegateIdentifier(): IdentityIdentifier
    {
        return $this->delegateIdentifier;
    }

    public function delegatorIdentifier(): IdentityIdentifier
    {
        return $this->delegatorIdentifier;
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

    public function approve(): void
    {
        if (! $this->status->isPending()) {
            throw new DomainException('Only pending delegations can be approved.');
        }

        $this->status = DelegationStatus::APPROVED;
        $this->approvedAt = new DateTimeImmutable();
    }

    public function revoke(): void
    {
        if (! $this->status->isApproved()) {
            throw new DomainException('Only approved delegations can be revoked.');
        }

        $this->status = DelegationStatus::REVOKED;
        $this->revokedAt = new DateTimeImmutable();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isRevoked(): bool
    {
        return $this->status->isRevoked();
    }
}
