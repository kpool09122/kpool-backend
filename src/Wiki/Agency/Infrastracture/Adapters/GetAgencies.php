<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastracture\Adapters;

use Application\Models\Wiki\Agency;
use Source\Shared\Infrastructure\Trait\WhereLike;
use Source\Wiki\Agency\Application\UseCase\Query\AgencyReadModel;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesInputPort;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesInterface;
use Source\Wiki\Agency\Application\UseCase\Query\GetAgencies\GetAgenciesOutputPort;

class GetAgencies implements GetAgenciesInterface
{
    use WhereLike;

    /**
     * @param GetAgenciesInputPort $input
     * @param GetAgenciesOutputPort $output
     * @return void
     * @throws \DateMalformedStringException
     */
    public function process(GetAgenciesInputPort $input, GetAgenciesOutputPort $output): void
    {
        $query = Agency::query()
            ->where('translation', $input->translation()->value);
        if ($input->order() && $input->sort()) {
            $query->orderBy($input->order(), $input->sort());
        }
        if ($input->searchWords()) {
            $this->whereLike($query, 'name', $input->searchWords());
        }
        $agencies = $query->paginate($input->limit());
        $agencyReadModels = [];
        foreach ($agencies->items() as $agency) {
            $foundedIn = null;
            $agencyReadModels[] = new AgencyReadModel(
                agencyId: $agency->id,
                name: $agency->name,
                CEO: $agency->CEO,
                foundedIn: $agency->founded_in?->toDateTimeImmutable(),
                description: $agency->description,
            );
        }

        $output->output(
            $agencyReadModels,
            $agencies->currentPage(),
            $agencies->lastPage(),
            $agencies->total(),
        );
    }
}
