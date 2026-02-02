<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Agency as AgencyModel;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

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
            new Slug($agencyModel->slug),
            Language::from($agencyModel->language),
            new Name($agencyModel->name),
            $agencyModel->normalized_name,
            new CEO($agencyModel->CEO),
            $agencyModel->normalized_CEO,
            $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
            new Description($agencyModel->description),
            new Version($agencyModel->version),
            $agencyModel->merger_id ? new PrincipalIdentifier($agencyModel->merger_id) : null,
            $agencyModel->merged_at?->toDateTimeImmutable(),
            $agencyModel->editor_id ? new PrincipalIdentifier($agencyModel->editor_id) : null,
            $agencyModel->approver_id ? new PrincipalIdentifier($agencyModel->approver_id) : null,
            (bool) $agencyModel->is_official,
            $agencyModel->owner_account_id ? new AccountIdentifier($agencyModel->owner_account_id) : null,
            $agencyModel->source_editor_id ? new PrincipalIdentifier($agencyModel->source_editor_id) : null,
            $agencyModel->translated_at?->toDateTimeImmutable(),
            $agencyModel->approved_at?->toDateTimeImmutable(),
        );
    }

    public function existsBySlug(Slug $slug): bool
    {
        return AgencyModel::query()
            ->where('slug', (string) $slug)
            ->exists();
    }

    /**
     * @return Agency[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array
    {
        $agencyModels = AgencyModel::query()
            ->where('translation_set_identifier', (string)$translationSetIdentifier)
            ->get();

        return $agencyModels->map(function (AgencyModel $agencyModel) {
            return new Agency(
                new AgencyIdentifier($agencyModel->id),
                new TranslationSetIdentifier($agencyModel->translation_set_identifier),
                new Slug($agencyModel->slug),
                Language::from($agencyModel->language),
                new Name($agencyModel->name),
                $agencyModel->normalized_name,
                new CEO($agencyModel->CEO),
                $agencyModel->normalized_CEO,
                $agencyModel->founded_in ? new FoundedIn($agencyModel->founded_in->toDateTimeImmutable()) : null,
                new Description($agencyModel->description),
                new Version($agencyModel->version),
                $agencyModel->merger_id ? new PrincipalIdentifier($agencyModel->merger_id) : null,
                $agencyModel->merged_at?->toDateTimeImmutable(),
                $agencyModel->editor_id ? new PrincipalIdentifier($agencyModel->editor_id) : null,
                $agencyModel->approver_id ? new PrincipalIdentifier($agencyModel->approver_id) : null,
                (bool) $agencyModel->is_official,
                $agencyModel->owner_account_id ? new AccountIdentifier($agencyModel->owner_account_id) : null,
                $agencyModel->source_editor_id ? new PrincipalIdentifier($agencyModel->source_editor_id) : null,
                $agencyModel->translated_at?->toDateTimeImmutable(),
                $agencyModel->approved_at?->toDateTimeImmutable(),
            );
        })->toArray();
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
                'slug' => (string)$agency->slug(),
                'name' => (string)$agency->name(),
                'normalized_name' => $agency->normalizedName(),
                'CEO' => (string)$agency->CEO(),
                'normalized_CEO' => $agency->normalizedCEO(),
                'founded_in' => $agency->foundedIn(),
                'description' => (string)$agency->description(),
                'editor_id' => $agency->editorIdentifier() ? (string) $agency->editorIdentifier() : null,
                'approver_id' => $agency->approverIdentifier() ? (string) $agency->approverIdentifier() : null,
                'merger_id' => $agency->mergerIdentifier() ? (string) $agency->mergerIdentifier() : null,
                'merged_at' => $agency->mergedAt(),
                'version' => $agency->version()->value(),
                'is_official' => $agency->isOfficial(),
                'owner_account_id' => $agency->ownerAccountIdentifier() ? (string) $agency->ownerAccountIdentifier() : null,
                'source_editor_id' => $agency->sourceEditorIdentifier() ? (string) $agency->sourceEditorIdentifier() : null,
                'translated_at' => $agency->translatedAt(),
                'approved_at' => $agency->approvedAt(),
            ]
        );
    }
}
