<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class Affiliation
{
    public function __construct(
        private readonly AffiliationIdentifier $affiliationIdentifier,
        private readonly AccountIdentifier $agencyAccountIdentifier,
        private readonly AccountIdentifier $talentAccountIdentifier,
        private readonly AccountIdentifier $requestedBy,
        private AffiliationStatus $status,
        private ?AffiliationTerms $terms,
        private readonly DateTimeImmutable $requestedAt,
        private ?DateTimeImmutable $activatedAt,
        private ?DateTimeImmutable $terminatedAt,
    ) {
    }

    public function affiliationIdentifier(): AffiliationIdentifier
    {
        return $this->affiliationIdentifier;
    }

    public function agencyAccountIdentifier(): AccountIdentifier
    {
        return $this->agencyAccountIdentifier;
    }

    public function talentAccountIdentifier(): AccountIdentifier
    {
        return $this->talentAccountIdentifier;
    }

    public function requestedBy(): AccountIdentifier
    {
        return $this->requestedBy;
    }

    public function status(): AffiliationStatus
    {
        return $this->status;
    }

    public function terms(): ?AffiliationTerms
    {
        return $this->terms;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function activatedAt(): ?DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function terminatedAt(): ?DateTimeImmutable
    {
        return $this->terminatedAt;
    }

    public function approve(): void
    {
        if (! $this->status->isPending()) {
            throw new DomainException('Only pending affiliations can be approved.');
        }

        $this->status = AffiliationStatus::ACTIVE;
        $this->activatedAt = new DateTimeImmutable();
    }

    public function terminate(): void
    {
        if (! $this->status->isActive()) {
            throw new DomainException('Only active affiliations can be terminated.');
        }

        $this->status = AffiliationStatus::TERMINATED;
        $this->terminatedAt = new DateTimeImmutable();
    }

    public function updateTerms(AffiliationTerms $terms): void
    {
        if (! $this->status->isActive()) {
            throw new DomainException('Terms can only be updated for active affiliations.');
        }

        $this->terms = $terms;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTerminated(): bool
    {
        return $this->status->isTerminated();
    }

    public function isRequestedByAgency(): bool
    {
        return (string) $this->requestedBy === (string) $this->agencyAccountIdentifier;
    }

    public function isRequestedByTalent(): bool
    {
        return (string) $this->requestedBy === (string) $this->talentAccountIdentifier;
    }

    public function approverAccountIdentifier(): AccountIdentifier
    {
        return $this->isRequestedByAgency()
            ? $this->talentAccountIdentifier
            : $this->agencyAccountIdentifier;
    }
}
