<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Agency as AgencyModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;

class AgencyRepository implements AgencyRepositoryInterface
{
    public function findById(AgencyIdentifier $agencyIdentifier): ?Agency
    {
        $agencyModel = AgencyModel::query()
            ->where('id', (string)$agencyIdentifier)
            ->first();

        if ($agencyModel === null) {
            return null;
        }

        return new Agency(
            new AgencyIdentifier($agencyModel->id),
            new TranslationSetIdentifier($agencyModel->translation_set_identifier),
            Language::from($agencyModel->language),
            new AgencyName($agencyModel->name),
            $agencyModel->normalized_name,
            new CEO($agencyModel->CEO),
            $agencyModel->normalized_CEO,
            $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
            new Description($agencyModel->description),
            new Version($agencyModel->version),
        );
    }

    public function save(Agency $agency): void
    {
        AgencyModel::query()->updateOrCreate(
            [
                'id' => (string)$agency->agencyIdentifier(),
                'language' => $agency->language()->value,
            ],
            [
                'translation_set_identifier' => (string)$agency->translationSetIdentifier(),
                'name' => (string)$agency->name(),
                'normalized_name' => $agency->normalizedName(),
                'CEO' => (string)$agency->CEO(),
                'normalized_CEO' => $agency->normalizedCEO(),
                'founded_in' => $agency->foundedIn(),
                'description' => (string)$agency->description(),
                'version' => $agency->version()->value(),
            ]
        );
    }
}
