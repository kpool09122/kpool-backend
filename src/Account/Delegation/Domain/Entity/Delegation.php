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

class Delegation
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
        private ?DateTimeImmutable $rejectedAt,
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

    public function approverAccountIdentifier(): AccountIdentifier
    {
        return (string) $this->requestedByAccountIdentifier === (string) $this->delegateAccountIdentifier
            ? $this->delegatorAccountIdentifier
            : $this->delegateAccountIdentifier;
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

    public function rejectedAt(): ?DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejected();
    }

    public function approve(): void
    {
        if (! $this->isPending()) {
            throw new DomainException('Only pending delegations can be approved.');
        }
        $this->status = DelegationStatus::APPROVED;
        $this->approvedAt = new DateTimeImmutable();
    }

    public function reject(): void
    {
        if (! $this->isPending()) {
            throw new DomainException('Only pending delegations can be rejected.');
        }
        $this->status = DelegationStatus::REJECTED;
        $this->rejectedAt = new DateTimeImmutable();
    }
}
