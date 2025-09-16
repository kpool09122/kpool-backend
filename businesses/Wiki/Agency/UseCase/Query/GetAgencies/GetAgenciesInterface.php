<?php

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgencies;

use Businesses\Wiki\Agency\UseCase\Query\AgencyReadModel;

interface GetAgenciesInterface
{
    /**
     * @param GetAgenciesInputPort $input
     * @return list<AgencyReadModel>
     */
    public function process(GetAgenciesInputPort $input): array;
}
