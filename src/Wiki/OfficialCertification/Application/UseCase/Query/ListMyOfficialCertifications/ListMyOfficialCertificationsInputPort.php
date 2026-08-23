<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ListMyOfficialCertificationsInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function accountIdentifier(): AccountIdentifier;

    public function accountCategory(): AccountCategory;

    public function status(): ?CertificationStatus;

    public function perPage(): int;
}
