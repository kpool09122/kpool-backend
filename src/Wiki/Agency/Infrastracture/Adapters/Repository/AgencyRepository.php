<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastracture\Adapters\Repository;

use Application\Models\Wiki\Agency as AgencyModel;
use Application\Models\Wiki\AgencyChangeRequest;
use Source\Shared\Domain\ValueObject\Translation;
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
            Translation::from($agencyModel->translation),
            new AgencyName($agencyModel->name),
            new CEO($agencyModel->CEO),
            $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
            new Description($agencyModel->description),
        );
    }

    public function findDraftById(AgencyIdentifier $agencyIdentifier): ?DraftAgency
    {
        $agencyModel = AgencyChangeRequest::query()
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
            Translation::from($agencyModel->translation),
            new AgencyName($agencyModel->name),
            new CEO($agencyModel->CEO),
            $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
            new Description($agencyModel->description),
            ApprovalStatus::from($agencyModel->status),
        );
    }

    public function saveDraft(DraftAgency $agency): void
    {
        AgencyChangeRequest::query()->updateOrCreate(
            [
                'id' => (string)$agency->agencyIdentifier(),
                'translation' => $agency->translation()->value,
                'editor_id' => (string)$agency->editorIdentifier(),
            ],
            [
                'published_id' => (string)$agency->publishedAgencyIdentifier(),
                'translation_set_identifier' => (string)$agency->translationSetIdentifier(),
                'name' => $agency->name(),
                'CEO' => $agency->CEO(),
                'founded_in' => $agency->foundedIn(),
                'description' => $agency->description(),
                'status' => $agency->status(),
            ]
        );
    }

    public function deleteDraft(DraftAgency $agency): void
    {
        AgencyChangeRequest::query()
            ->where('id', (string)$agency->agencyIdentifier())
            ->delete();
    }

    public function save(Agency $agency): void
    {
        AgencyModel::query()->updateOrCreate(
            [
                'id' => (string)$agency->agencyIdentifier(),
                'translation' => $agency->translation()->value,
            ],
            [
                'translation_set_identifier' => (string)$agency->translationSetIdentifier(),
                'name' => $agency->name(),
                'CEO' => $agency->CEO(),
                'founded_in' => $agency->foundedIn(),
                'description' => $agency->description(),
            ]
        );
    }

    public function findDraftsByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $agencyModels = AgencyChangeRequest::query()
            ->where('translation_set_identifier', (string)$translationSetIdentifier)
            ->get();

        return $agencyModels->map(function ($agencyModel) {
            return new DraftAgency(
                new AgencyIdentifier($agencyModel->id),
                $agencyModel->published_id ? new AgencyIdentifier($agencyModel->published_id) : null,
                $agencyModel->translation_set_identifier ? new TranslationSetIdentifier($agencyModel->translation_set_identifier) : null,
                new EditorIdentifier($agencyModel->editor_id),
                Translation::from($agencyModel->translation),
                new AgencyName($agencyModel->name),
                new CEO($agencyModel->CEO),
                $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
                new Description($agencyModel->description),
                ApprovalStatus::from($agencyModel->status),
            );
        })->toArray();
    }
}
