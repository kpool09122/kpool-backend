<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications;

use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ListOfficialCertificationsInput implements ListOfficialCertificationsInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ?CertificationStatus $status = null,
        private ?int $perPage = null,
    ) {
    }

    public function status(): ?CertificationStatus
    {
        return $this->status;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }
}
