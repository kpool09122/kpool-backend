<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\AgencySnapshot as AgencySnapshotModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\Repository\AgencySnapshotRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

class AgencySnapshotRepository implements AgencySnapshotRepositoryInterface
{
    public function save(AgencySnapshot $snapshot): void
    {
        AgencySnapshotModel::query()->create([
            'id' => (string)$snapshot->snapshotIdentifier(),
            'agency_id' => (string)$snapshot->agencyIdentifier(),
            'translation_set_identifier' => (string)$snapshot->translationSetIdentifier(),
            'slug' => (string)$snapshot->slug(),
            'language' => $snapshot->language()->value,
            'name' => (string)$snapshot->name(),
            'normalized_name' => $snapshot->normalizedName(),
            'CEO' => (string)$snapshot->CEO(),
            'normalized_CEO' => $snapshot->normalizedCEO(),
            'founded_in' => $snapshot->foundedIn()?->value(),
            'description' => (string)$snapshot->description(),
            'version' => $snapshot->version()->value(),
            'created_at' => $snapshot->createdAt(),
            'editor_id' => $snapshot->editorIdentifier() ? (string)$snapshot->editorIdentifier() : null,
            'approver_id' => $snapshot->approverIdentifier() ? (string)$snapshot->approverIdentifier() : null,
            'merger_id' => $snapshot->mergerIdentifier() ? (string)$snapshot->mergerIdentifier() : null,
            'merged_at' => $snapshot->mergedAt(),
            'source_editor_id' => $snapshot->sourceEditorIdentifier() ? (string)$snapshot->sourceEditorIdentifier() : null,
            'translated_at' => $snapshot->translatedAt(),
            'approved_at' => $snapshot->approvedAt(),
        ]);
    }

    public function findByAgencyIdentifier(AgencyIdentifier $agencyIdentifier): array
    {
        $models = AgencySnapshotModel::query()
            ->where('agency_id', (string)$agencyIdentifier)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map(fn (AgencySnapshotModel $model) => $this->toEntity($model))->toArray();
    }

    public function findByAgencyAndVersion(
        AgencyIdentifier $agencyIdentifier,
        Version $version
    ): ?AgencySnapshot {
        $model = AgencySnapshotModel::query()
            ->where('agency_id', (string)$agencyIdentifier)
            ->where('version', $version->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @inheritDoc
     */
    public function findByTranslationSetIdentifierAndVersion(
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version
    ): array {
        $models = AgencySnapshotModel::query()
            ->where('translation_set_identifier', (string)$translationSetIdentifier)
            ->where('version', $version->value())
            ->get();

        return $models->map(fn (AgencySnapshotModel $model) => $this->toEntity($model))->toArray();
    }

    private function toEntity(AgencySnapshotModel $model): AgencySnapshot
    {
        return new AgencySnapshot(
            new AgencySnapshotIdentifier($model->id),
            new AgencyIdentifier($model->agency_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new Slug($model->slug),
            Language::from($model->language),
            new Name($model->name),
            $model->normalized_name,
            new CEO($model->CEO),
            $model->normalized_CEO,
            $model->founded_in ? new FoundedIn($model->founded_in->toDateTimeImmutable()) : null,
            new Description($model->description),
            new Version($model->version),
            $model->created_at->toDateTimeImmutable(),
            $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            $model->merger_id ? new PrincipalIdentifier($model->merger_id) : null,
            $model->merged_at?->toDateTimeImmutable(),
            $model->source_editor_id ? new PrincipalIdentifier($model->source_editor_id) : null,
            $model->translated_at?->toDateTimeImmutable(),
            $model->approved_at?->toDateTimeImmutable(),
        );
    }
}
