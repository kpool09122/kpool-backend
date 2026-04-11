<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;

interface RequestCertificationInterface
{
    /**
     * @param RequestCertificationInputPort $input
     * @param RequestCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationAlreadyRequestedException
     */
    public function process(RequestCertificationInputPort $input, RequestCertificationOutputPort $output): void;
}
