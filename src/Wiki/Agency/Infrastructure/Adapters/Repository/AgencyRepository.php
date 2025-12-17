<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Agency as AgencyModel;
use Application\Models\Wiki\DraftAgency as DraftAgencyModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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

    public function findDraftById(AgencyIdentifier $agencyIdentifier): ?DraftAgency
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
            new EditorIdentifier($agencyModel->editor_id),
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

    public function saveDraft(DraftAgency $agency): void
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

    public function deleteDraft(DraftAgency $agency): void
    {
        DraftAgencyModel::query()
            ->where('id', (string)$agency->agencyIdentifier())
            ->delete();
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

    public function findDraftsByTranslationSet(
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
                new EditorIdentifier($agencyModel->editor_id),
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
