<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;

readonly class ApproveCertificationInput implements ApproveCertificationInputPort
{
    public function __construct(
        private CertificationIdentifier $certificationIdentifier,
    ) {
    }

    public function certificationIdentifier(): CertificationIdentifier
    {
        return $this->certificationIdentifier;
    }
}
