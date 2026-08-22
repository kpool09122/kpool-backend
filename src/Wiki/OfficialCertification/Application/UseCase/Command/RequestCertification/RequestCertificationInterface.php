<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface RequestCertificationInterface
{
    /**
     * @param RequestCertificationInputPort $input
     * @param RequestCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationAlreadyRequestedException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(RequestCertificationInputPort $input, RequestCertificationOutputPort $output): void;
}
