<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class AccountDelegation
{
    public function __construct(
        private DelegationIdentifier $delegationIdentifier,
        private AffiliationIdentifier $affiliationIdentifier,
        private AccountIdentifier $delegateAccountIdentifier,
        private AccountIdentifier $delegatorAccountIdentifier,
        private AccountIdentifier $requestedByAccountIdentifier,
        private DelegationStatus $status,
        private DelegationDirection $direction,
        private DateTimeImmutable $requestedAt,
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
}
