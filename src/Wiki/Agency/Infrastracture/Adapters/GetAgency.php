<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastracture\Adapters;

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

        $foundedIn = null;
        if ($agency->founded_in instanceof \Illuminate\Support\Carbon) {
            $foundedIn = $agency->founded_in->toDateTimeImmutable();
        } elseif (is_string($agency->founded_in) && $agency->founded_in !== '') {
            $foundedIn = new \DateTimeImmutable($agency->founded_in);
        }

        return new AgencyReadModel(
            agencyId: $agency->id,
            name: $agency->name,
            CEO: $agency->CEO,
            foundedIn: $foundedIn,
            description: $agency->description,
        );
    }
}
