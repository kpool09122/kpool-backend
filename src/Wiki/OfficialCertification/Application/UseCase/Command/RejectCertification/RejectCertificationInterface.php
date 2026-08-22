<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface RejectCertificationInterface
{
    /**
     * @param RejectCertificationInputPort $input
     * @param RejectCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationNotFoundException
     * @throws OfficialCertificationInvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectCertificationInputPort $input, RejectCertificationOutputPort $output): void;
}
