<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\SongSnapshot as SongSnapshotModel;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\Repository\SongSnapshotRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;

class SongSnapshotRepository implements SongSnapshotRepositoryInterface
{
    public function save(SongSnapshot $snapshot): void
    {
        SongSnapshotModel::query()->create([
            'id' => (string)$snapshot->snapshotIdentifier(),
            'song_id' => (string)$snapshot->songIdentifier(),
            'translation_set_identifier' => (string)$snapshot->translationSetIdentifier(),
            'language' => $snapshot->language()->value,
            'name' => (string)$snapshot->name(),
            'agency_id' => $snapshot->agencyIdentifier() ? (string)$snapshot->agencyIdentifier() : null,
            'belong_identifiers' => $this->fromBelongIdentifiers($snapshot->belongIdentifiers()),
            'lyricist' => (string)$snapshot->lyricist(),
            'composer' => (string)$snapshot->composer(),
            'release_date' => $snapshot->releaseDate()?->value(),
            'overview' => (string)$snapshot->overView(),
            'cover_image_path' => $snapshot->coverImagePath() ? (string)$snapshot->coverImagePath() : null,
            'music_video_link' => $snapshot->musicVideoLink() ? (string)$snapshot->musicVideoLink() : null,
            'version' => $snapshot->version()->value(),
            'created_at' => $snapshot->createdAt(),
        ]);
    }

    public function findBySongIdentifier(SongIdentifier $songIdentifier): array
    {
        $models = SongSnapshotModel::query()
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
            ->where('song_id', (string)$songIdentifier)
            ->where('version', $version->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @param BelongIdentifier[] $belongIdentifiers
     * @return string[]
     */
    private function fromBelongIdentifiers(array $belongIdentifiers): array
    {
        return array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $belongIdentifiers,
        );
    }

    /**
     * @param array<int, string>|null $belongIdentifiers
     * @return BelongIdentifier[]
     */
    private function toBelongIdentifiers(?array $belongIdentifiers): array
    {
        $identifiers = $belongIdentifiers ?? [];

        return array_map(
            static fn (string $belongId): BelongIdentifier => new BelongIdentifier($belongId),
            $identifiers,
        );
    }

    private function toEntity(SongSnapshotModel $model): SongSnapshot
    {
        return new SongSnapshot(
            new SongSnapshotIdentifier($model->id),
            new SongIdentifier($model->song_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->language),
            new SongName($model->name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            $this->toBelongIdentifiers($model->belong_identifiers),
            new Lyricist($model->lyricist),
            new Composer($model->composer),
            $model->release_date ? new ReleaseDate($model->release_date->toDateTimeImmutable()) : null,
            new Overview($model->overview),
            $model->cover_image_path ? new ImagePath($model->cover_image_path) : null,
            $model->music_video_link ? new ExternalContentLink($model->music_video_link) : null,
            new Version($model->version),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
