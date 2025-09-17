<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgencies;

interface GetAgenciesInterface
{
    /**
     * @param GetAgenciesInputPort $input
     * @param GetAgenciesOutputPort $output
     * @return void
     * @throws \DateMalformedStringException
     */
    public function process(GetAgenciesInputPort $input, GetAgenciesOutputPort $output): void;
}
