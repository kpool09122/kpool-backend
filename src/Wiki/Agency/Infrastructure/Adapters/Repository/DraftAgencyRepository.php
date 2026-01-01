<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\DraftAgency as DraftAgencyModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class DraftAgencyRepository implements DraftAgencyRepositoryInterface
{
    public function findById(AgencyIdentifier $agencyIdentifier): ?DraftAgency
    {
        $agencyModel = DraftAgencyModel::query()
            ->where('id', $agencyIdentifier)
            ->first();

        if ($agencyModel === null) {
            return null;
        }

        return new DraftAgency(
            new AgencyIdentifier($agencyModel->id),
            $agencyModel->published_id ? new AgencyIdentifier($agencyModel->published_id) : null,
            $agencyModel->translation_set_identifier ? new TranslationSetIdentifier($agencyModel->translation_set_identifier) : null,
            new PrincipalIdentifier($agencyModel->editor_id),
            Language::from($agencyModel->language),
            new AgencyName($agencyModel->name),
            $agencyModel->normalized_name,
            new CEO($agencyModel->CEO),
            $agencyModel->normalized_CEO,
            $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
            new Description($agencyModel->description),
            ApprovalStatus::from($agencyModel->status),
        );
    }

    public function save(DraftAgency $agency): void
    {
        DraftAgencyModel::query()->updateOrCreate(
            [
                'id' => (string)$agency->agencyIdentifier(),
                'language' => $agency->language()->value,
                'editor_id' => (string)$agency->editorIdentifier(),
            ],
            [
                'published_id' => (string)$agency->publishedAgencyIdentifier(),
                'translation_set_identifier' => (string)$agency->translationSetIdentifier(),
                'name' => (string)$agency->name(),
                'normalized_name' => $agency->normalizedName(),
                'CEO' => (string)$agency->CEO(),
                'normalized_CEO' => $agency->normalizedCEO(),
                'founded_in' => $agency->foundedIn(),
                'description' => (string)$agency->description(),
                'status' => $agency->status()->value,
            ]
        );
    }

    public function delete(DraftAgency $agency): void
    {
        DraftAgencyModel::query()
            ->where('id', (string)$agency->agencyIdentifier())
            ->delete();
    }

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftAgency[]
     */
    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $agencyModels = DraftAgencyModel::query()
            ->where('translation_set_identifier', (string)$translationSetIdentifier)
            ->get();

        return $agencyModels->map(function (DraftAgencyModel $agencyModel) {
            return new DraftAgency(
                new AgencyIdentifier($agencyModel->id),
                $agencyModel->published_id ? new AgencyIdentifier($agencyModel->published_id) : null,
                $agencyModel->translation_set_identifier ? new TranslationSetIdentifier($agencyModel->translation_set_identifier) : null,
                new PrincipalIdentifier($agencyModel->editor_id),
                Language::from($agencyModel->language),
                new AgencyName($agencyModel->name),
                $agencyModel->normalized_name,
                new CEO($agencyModel->CEO),
                $agencyModel->normalized_CEO,
                $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
                new Description($agencyModel->description),
                ApprovalStatus::from($agencyModel->status),
            );
        })->toArray();
    }
}
