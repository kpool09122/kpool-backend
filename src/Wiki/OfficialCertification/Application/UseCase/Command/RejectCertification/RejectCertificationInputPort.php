<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;

interface RejectCertificationInputPort
{
    public function certificationIdentifier(): CertificationIdentifier;
}
