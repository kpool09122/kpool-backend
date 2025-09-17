<?php

declare(strict_types=1);

namespace Application\Adapters\Wiki\Agency\Query;

use Application\Models\Wiki\Agency;
use Businesses\Wiki\Agency\UseCase\Exception\AgencyNotFoundException;
use Businesses\Wiki\Agency\UseCase\Query\AgencyReadModel;
use Businesses\Wiki\Agency\UseCase\Query\GetAgency\GetAgencyInputPort;
use Businesses\Wiki\Agency\UseCase\Query\GetAgency\GetAgencyInterface;
use DateTimeImmutable;

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
            foundedIn: $agency->founded_in ? new DateTimeImmutable($agency->founded_in) : null,
            description: $agency->description,
        );
    }
}
