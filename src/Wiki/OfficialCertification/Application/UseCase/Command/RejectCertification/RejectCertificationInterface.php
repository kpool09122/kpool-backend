<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;

interface RejectCertificationInterface
{
    /**
     * @param RejectCertificationInputPort $input
     * @return OfficialCertification
     * @throws OfficialCertificationNotFoundException
     */
    public function process(RejectCertificationInputPort $input): OfficialCertification;
}
