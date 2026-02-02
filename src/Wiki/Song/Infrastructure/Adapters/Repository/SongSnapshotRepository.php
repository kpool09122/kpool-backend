<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Group;
use Application\Models\Wiki\SongSnapshot as SongSnapshotModel;
use Application\Models\Wiki\Talent;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\Repository\SongSnapshotRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;

class SongSnapshotRepository implements SongSnapshotRepositoryInterface
{
    public function save(SongSnapshot $snapshot): void
    {
        /** @var SongSnapshotModel $snapshotModel */
        $snapshotModel = SongSnapshotModel::query()->create([
            'id' => (string)$snapshot->snapshotIdentifier(),
            'song_id' => (string)$snapshot->songIdentifier(),
            'translation_set_identifier' => (string)$snapshot->translationSetIdentifier(),
            'slug' => (string)$snapshot->slug(),
            'language' => $snapshot->language()->value,
            'name' => (string)$snapshot->name(),
            'agency_id' => $snapshot->agencyIdentifier() ? (string)$snapshot->agencyIdentifier() : null,
            'lyricist' => (string)$snapshot->lyricist(),
            'composer' => (string)$snapshot->composer(),
            'release_date' => $snapshot->releaseDate()?->value(),
            'overview' => (string)$snapshot->overView(),
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

        $groupId = $snapshot->groupIdentifier() ? [(string)$snapshot->groupIdentifier()] : [];
        $snapshotModel->groups()->sync($groupId);

        $talentId = $snapshot->talentIdentifier() ? [(string)$snapshot->talentIdentifier()] : [];
        $snapshotModel->talents()->sync($talentId);
    }

    public function findBySongIdentifier(SongIdentifier $songIdentifier): array
    {
        $models = SongSnapshotModel::query()
            ->with(['groups', 'talents'])
            ->where('song_id', (string)$songIdentifier)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map(fn (SongSnapshotModel $model) => $this->toEntity($model))->toArray();
    }

    public function findBySongAndVersion(
        SongIdentifier $songIdentifier,
        Version $version
    ): ?SongSnapshot {
        $model = SongSnapshotModel::query()
            ->with(['groups', 'talents'])
            ->where('song_id', (string)$songIdentifier)
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
        $models = SongSnapshotModel::query()
            ->with(['groups', 'talents'])
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->where('version', $version->value())
            ->get();

        return $models->map(fn (SongSnapshotModel $model) => $this->toEntity($model))->toArray();
    }

    private function toEntity(SongSnapshotModel $model): SongSnapshot
    {
        /** @var Group|null $group */
        $group = $model->groups->first();
        $groupIdentifier = $group ? new GroupIdentifier($group->id) : null;

        /** @var Talent|null $talent */
        $talent = $model->talents->first();
        $talentIdentifier = $talent ? new TalentIdentifier($talent->id) : null;

        return new SongSnapshot(
            new SongSnapshotIdentifier($model->id),
            new SongIdentifier($model->song_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new Slug($model->slug),
            Language::from($model->language),
            new SongName($model->name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            $groupIdentifier,
            $talentIdentifier,
            new Lyricist($model->lyricist),
            new Composer($model->composer),
            $model->release_date ? new ReleaseDate($model->release_date->toDateTimeImmutable()) : null,
            new Overview($model->overview),
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
