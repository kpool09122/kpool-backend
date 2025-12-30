<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\GroupSnapshot as GroupSnapshotModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Group\Domain\Repository\GroupSnapshotRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

class GroupSnapshotRepository implements GroupSnapshotRepositoryInterface
{
    public function save(GroupSnapshot $snapshot): void
    {
        GroupSnapshotModel::query()->create([
            'id' => (string)$snapshot->snapshotIdentifier(),
            'group_id' => (string)$snapshot->groupIdentifier(),
            'translation_set_identifier' => (string)$snapshot->translationSetIdentifier(),
            'translation' => $snapshot->language()->value,
            'name' => (string)$snapshot->name(),
            'normalized_name' => $snapshot->normalizedName(),
            'agency_id' => $snapshot->agencyIdentifier() ? (string)$snapshot->agencyIdentifier() : null,
            'description' => (string)$snapshot->description(),
            'song_identifiers' => $this->fromSongIdentifiers($snapshot->songIdentifiers()),
            'image_path' => $snapshot->imagePath() ? (string)$snapshot->imagePath() : null,
            'version' => $snapshot->version()->value(),
            'created_at' => $snapshot->createdAt(),
        ]);
    }

    public function findByGroupIdentifier(GroupIdentifier $groupIdentifier): array
    {
        $models = GroupSnapshotModel::query()
            ->where('group_id', (string)$groupIdentifier)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map(fn (GroupSnapshotModel $model) => $this->toEntity($model))->toArray();
    }

    public function findByGroupAndVersion(
        GroupIdentifier $groupIdentifier,
        Version $version
    ): ?GroupSnapshot {
        $model = GroupSnapshotModel::query()
            ->where('group_id', (string)$groupIdentifier)
            ->where('version', $version->value())
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @param SongIdentifier[] $songIdentifiers
     * @return string[]
     */
    private function fromSongIdentifiers(array $songIdentifiers): array
    {
        return array_map(
            static fn (SongIdentifier $identifier): string => (string) $identifier,
            $songIdentifiers,
        );
    }

    /**
     * @param array<int, string>|null $songIdentifiers
     * @return SongIdentifier[]
     */
    private function toSongIdentifiers(?array $songIdentifiers): array
    {
        $identifiers = $songIdentifiers ?? [];

        return array_map(
            static fn (string $songId): SongIdentifier => new SongIdentifier($songId),
            $identifiers,
        );
    }

    private function toEntity(GroupSnapshotModel $model): GroupSnapshot
    {
        return new GroupSnapshot(
            new GroupSnapshotIdentifier($model->id),
            new GroupIdentifier($model->group_id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->translation),
            new GroupName($model->name),
            $model->normalized_name,
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            $this->toSongIdentifiers($model->song_identifiers),
            $model->image_path ? new ImagePath($model->image_path) : null,
            new Version($model->version),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
