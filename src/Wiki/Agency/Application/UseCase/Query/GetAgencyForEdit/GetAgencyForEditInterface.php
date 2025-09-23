<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgencyForEdit;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Query\AgencyReadModel;

interface GetAgencyForEditInterface
{
    /**
     * @param GetAgencyForEditInputPort $input
     * @return AgencyReadModel
     * @throws AgencyNotFoundException
     * @throws \DateMalformedStringException
     */
    public function process(GetAgencyForEditInputPort $input): AgencyReadModel;
}
