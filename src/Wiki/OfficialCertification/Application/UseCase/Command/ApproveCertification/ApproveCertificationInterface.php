<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;

interface ApproveCertificationInterface
{
    /**
     * @param ApproveCertificationInputPort $input
     * @param ApproveCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationNotFoundException
     * @throws OfficialCertificationInvalidStatusException
     */
    public function process(ApproveCertificationInputPort $input, ApproveCertificationOutputPort $output): void;
}
