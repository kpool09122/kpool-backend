<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgency;

use Businesses\Wiki\Agency\UseCase\Exception\AgencyNotFoundException;
use Businesses\Wiki\Agency\UseCase\Query\AgencyReadModel;

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
