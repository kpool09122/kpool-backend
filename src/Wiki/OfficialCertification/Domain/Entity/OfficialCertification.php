<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class OfficialCertification
{
    public function __construct(
        private readonly CertificationIdentifier $certificationIdentifier,
        private readonly ResourceType $resourceType,
        private readonly ResourceIdentifier $resourceIdentifier,
        private readonly AccountIdentifier $ownerAccountIdentifier,
        private CertificationStatus $status,
        private readonly DateTimeImmutable $requestedAt,
        private ?DateTimeImmutable $approvedAt,
        private ?DateTimeImmutable $rejectedAt,
    ) {
    }

    public function certificationIdentifier(): CertificationIdentifier
    {
        return $this->certificationIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function resourceIdentifier(): ResourceIdentifier
    {
        return $this->resourceIdentifier;
    }

    public function ownerAccountIdentifier(): AccountIdentifier
    {
        return $this->ownerAccountIdentifier;
    }

    public function status(): CertificationStatus
    {
        return $this->status;
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

    public function approve(): void
    {
        if (! $this->status->isPending()) {
            throw new DomainException('Only pending certifications can be approved.');
        }

        $this->status = CertificationStatus::APPROVED;
        $this->approvedAt = new DateTimeImmutable();
    }

    public function reject(): void
    {
        if (! $this->status->isPending()) {
            throw new DomainException('Only pending certifications can be rejected.');
        }

        $this->status = CertificationStatus::REJECTED;
        $this->rejectedAt = new DateTimeImmutable();
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
}
