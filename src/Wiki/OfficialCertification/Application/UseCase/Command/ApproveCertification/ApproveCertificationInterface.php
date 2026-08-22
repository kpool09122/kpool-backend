<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ApproveCertificationInterface
{
    /**
     * @param ApproveCertificationInputPort $input
     * @param ApproveCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationNotFoundException
     * @throws OfficialCertificationInvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveCertificationInputPort $input, ApproveCertificationOutputPort $output): void;
}
