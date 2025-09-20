<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastracture\Adapters\Repository;

use Application\Models\Wiki\Agency as AgencyModel;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;

class AgencyRepository implements AgencyRepositoryInterface
{
    public function findById(AgencyIdentifier $agencyIdentifier): ?Agency
    {
        $agencyModel = AgencyModel::query()
            ->where('id', $agencyIdentifier)
            ->first();

        if ($agencyModel === null) {
            return null;
        }

        return new Agency(
            new AgencyIdentifier($agencyModel->id),
            Translation::from($agencyModel->translation),
            new AgencyName($agencyModel->name),
            new CEO($agencyModel->CEO),
            $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
            new Description($agencyModel->description),
        );
    }

    public function save(Agency $agency): void
    {
        AgencyModel::query()->updateOrCreate(
            [
              'id' => (string)$agency->agencyIdentifier(),
              'translation' => $agency->translation()->value,
            ],
            [
                'name' => $agency->name(),
                'CEO' => $agency->CEO(),
                'founded_in' => $agency->foundedIn(),
                'description' => $agency->description(),
            ]
        );
    }
}
