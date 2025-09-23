<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastracture\Adapters\Query;

use Application\Models\Wiki\Agency;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\UseCase\Query\AgencyReadModel;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgency\GetAgencyInputPort;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgency\GetAgencyInterface;

class GetAgency implements GetAgencyInterface
{
    /**
     * @param GetAgencyInputPort $input
     * @return AgencyReadModel
     * @throws AgencyNotFoundException
     * @throws \DateMalformedStringException
     */
    public function process(GetAgencyInputPort $input): AgencyReadModel
    {
        $agency = Agency::query()
            ->where('id', (string)$input->agencyIdentifier())
            ->where('translation', $input->translation()->value)
            ->first();

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        return new AgencyReadModel(
            agencyId: $agency->id,
            name: $agency->name,
            CEO: $agency->CEO,
            foundedIn: $agency->founded_in?->toDateTimeImmutable(),
            description: $agency->description,
        );
    }
}
