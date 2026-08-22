<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ApproveCertificationInputPort
{
    public function certificationIdentifier(): CertificationIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;
}
