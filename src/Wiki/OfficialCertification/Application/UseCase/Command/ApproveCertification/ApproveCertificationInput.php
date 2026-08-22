<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ApproveCertificationInput implements ApproveCertificationInputPort
{
    public function __construct(
        private CertificationIdentifier $certificationIdentifier,
        private PrincipalIdentifier $operatorPrincipalIdentifier,
    ) {
    }

    public function certificationIdentifier(): CertificationIdentifier
    {
        return $this->certificationIdentifier;
    }

    public function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier;
    }
}
