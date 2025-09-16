<?php

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgency;

use Businesses\Wiki\Agency\UseCase\Query\AgencyReadModel;

interface GetAgencyInterface
{
    public function process(GetAgencyInputPort $input): AgencyReadModel;
}
