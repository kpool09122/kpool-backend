<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;

interface ApproveCertificationInterface
{
    public function process(ApproveCertificationInputPort $input): OfficialCertification;
}
