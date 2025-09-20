<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Query\AgencyReadModel;

interface GetAgencyInterface
{
    /**
     * @param GetAgencyInputPort $input
     * @return AgencyReadModel
     * @throws AgencyNotFoundException
     * @throws \DateMalformedStringException
     */
    public function process(GetAgencyInputPort $input): AgencyReadModel;
}
