<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications;

use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ListOfficialCertificationsInputPort
{
    public function status(): ?CertificationStatus;

    public function principalIdentifier(): PrincipalIdentifier;

    public function perPage(): int;
}
