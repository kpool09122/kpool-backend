<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;

interface ApproveCertificationInputPort
{
    public function certificationIdentifier(): CertificationIdentifier;
}
