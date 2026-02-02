<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\DraftAgency as DraftAgencyModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

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
            new Slug($agencyModel->slug),
            $agencyModel->editor_id ? new PrincipalIdentifier($agencyModel->editor_id) : null,
            Language::from($agencyModel->language),
            new Name($agencyModel->name),
            $agencyModel->normalized_name,
            new CEO($agencyModel->CEO),
            $agencyModel->normalized_CEO,
            $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
            new Description($agencyModel->description),
            ApprovalStatus::from($agencyModel->status),
            $agencyModel->approver_id ? new PrincipalIdentifier($agencyModel->approver_id) : null,
            $agencyModel->merger_id ? new PrincipalIdentifier($agencyModel->merger_id) : null,
            null,
            $agencyModel->source_editor_id ? new PrincipalIdentifier($agencyModel->source_editor_id) : null,
            $agencyModel->translated_at?->toDateTimeImmutable(),
            $agencyModel->approved_at?->toDateTimeImmutable(),
        );
    }

    public function save(DraftAgency $agency): void
    {
        DraftAgencyModel::query()->updateOrCreate(
            [
                'id' => (string)$agency->agencyIdentifier(),
                'language' => $agency->language()->value,
            ],
            [
                'editor_id' => $agency->editorIdentifier() ? (string)$agency->editorIdentifier() : null,
                'published_id' => $agency->publishedAgencyIdentifier() ? (string)$agency->publishedAgencyIdentifier() : null,
                'translation_set_identifier' => (string)$agency->translationSetIdentifier(),
                'slug' => (string)$agency->slug(),
                'name' => (string)$agency->name(),
                'normalized_name' => $agency->normalizedName(),
                'CEO' => (string)$agency->CEO(),
                'normalized_CEO' => $agency->normalizedCEO(),
                'founded_in' => $agency->foundedIn(),
                'description' => (string)$agency->description(),
                'status' => $agency->status()->value,
                'approver_id' => $agency->approverIdentifier() ? (string)$agency->approverIdentifier() : null,
                'merger_id' => $agency->mergerIdentifier() ? (string)$agency->mergerIdentifier() : null,
                'source_editor_id' => $agency->sourceEditorIdentifier() ? (string)$agency->sourceEditorIdentifier() : null,
                'translated_at' => $agency->translatedAt(),
                'approved_at' => $agency->approvedAt(),
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

        return $agencyModels->map(static function (DraftAgencyModel $agencyModel) {
            return new DraftAgency(
                new AgencyIdentifier($agencyModel->id),
                $agencyModel->published_id ? new AgencyIdentifier($agencyModel->published_id) : null,
                $agencyModel->translation_set_identifier ? new TranslationSetIdentifier($agencyModel->translation_set_identifier) : null,
                new Slug($agencyModel->slug),
                $agencyModel->editor_id ? new PrincipalIdentifier($agencyModel->editor_id) : null,
                Language::from($agencyModel->language),
                new Name($agencyModel->name),
                $agencyModel->normalized_name,
                new CEO($agencyModel->CEO),
                $agencyModel->normalized_CEO,
                $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
                new Description($agencyModel->description),
                ApprovalStatus::from($agencyModel->status),
                $agencyModel->approver_id ? new PrincipalIdentifier($agencyModel->approver_id) : null,
                $agencyModel->merger_id ? new PrincipalIdentifier($agencyModel->merger_id) : null,
                null,
                $agencyModel->source_editor_id ? new PrincipalIdentifier($agencyModel->source_editor_id) : null,
                $agencyModel->translated_at?->toDateTimeImmutable(),
                $agencyModel->approved_at?->toDateTimeImmutable(),
            );
        })->toArray();
    }
}
