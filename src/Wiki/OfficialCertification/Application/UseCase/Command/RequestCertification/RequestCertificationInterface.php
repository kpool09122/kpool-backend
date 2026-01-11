<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;

interface RequestCertificationInterface
{
    public function process(RequestCertificationInputPort $input): OfficialCertification;
}
