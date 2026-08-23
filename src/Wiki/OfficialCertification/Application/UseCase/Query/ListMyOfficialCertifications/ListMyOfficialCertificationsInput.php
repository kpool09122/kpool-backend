<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ListMyOfficialCertificationsInput implements ListMyOfficialCertificationsInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private AccountIdentifier $accountIdentifier,
        private AccountCategory $accountCategory,
        private ?CertificationStatus $status = null,
        private ?int $perPage = null,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountCategory(): AccountCategory
    {
        return $this->accountCategory;
    }

    public function status(): ?CertificationStatus
    {
        return $this->status;
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }
}
