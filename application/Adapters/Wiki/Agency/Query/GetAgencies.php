<?php

declare(strict_types=1);

namespace Application\Adapters\Wiki\Agency\Query;

use Application\Models\Wiki\Agency;
use Application\Shared\Trait\WhereLike;
use Businesses\Wiki\Agency\UseCase\Query\AgencyReadModel;
use Businesses\Wiki\Agency\UseCase\Query\GetAgencies\GetAgenciesInputPort;
use Businesses\Wiki\Agency\UseCase\Query\GetAgencies\GetAgenciesInterface;
use Businesses\Wiki\Agency\UseCase\Query\GetAgencies\GetAgenciesOutputPort;
use DateTimeImmutable;

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
            $agencyReadModels[] = new AgencyReadModel(
                agencyId: $agency->id,
                name: $agency->name,
                CEO: $agency->CEO,
                foundedIn: $agency->founded_in ? new DateTimeImmutable($agency->founded_in) : null,
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
