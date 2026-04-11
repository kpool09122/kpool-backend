<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;

interface RejectCertificationInterface
{
    /**
     * @param RejectCertificationInputPort $input
     * @param RejectCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationNotFoundException
     * @throws OfficialCertificationInvalidStatusException
     */
    public function process(RejectCertificationInputPort $input, RejectCertificationOutputPort $output): void;
}
