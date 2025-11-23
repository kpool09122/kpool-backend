<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastracture\Adapters\Repository;

use Application\Models\Wiki\Group as GroupModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

final class GroupRepository implements GroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?Group
    {
        $groupModel = GroupModel::query()
            ->where('id', (string) $groupIdentifier)
            ->whereNull('editor_id')
            ->first();

        if ($groupModel === null || $groupModel->version === null) {
            return null;
        }

        return $this->mapGroupEntity($groupModel);
    }

    public function findDraftById(GroupIdentifier $groupIdentifier): ?DraftGroup
    {
        $draftModel = GroupModel::query()
            ->where('id', (string) $groupIdentifier)
            ->whereNotNull('editor_id')
            ->first();

        if ($draftModel === null) {
            return null;
        }

        return $this->mapDraftEntity($draftModel);
    }

    public function save(Group $group): void
    {
        GroupModel::query()->updateOrCreate(
            [
                'id' => (string) $group->groupIdentifier(),
            ],
            [
                'published_id' => null,
                'translation_set_identifier' => (string) $group->translationSetIdentifier(),
                'editor_id' => null,
                'translation' => $group->translation()->value,
                'name' => (string) $group->name(),
                'agency_id' => $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
                'description' => (string) $group->description(),
                'song_identifiers' => $this->extractSongIdentifiers($group->songIdentifiers()),
                'image_path' => $group->imagePath() ? (string) $group->imagePath() : null,
                'status' => null,
                'version' => $group->version()->value(),
            ],
        );
    }

    public function saveDraft(DraftGroup $group): void
    {
        GroupModel::query()->updateOrCreate(
            [
                'id' => (string) $group->groupIdentifier(),
            ],
            [
                'published_id' => $group->publishedGroupIdentifier()
                    ? (string) $group->publishedGroupIdentifier()
                    : null,
                'translation_set_identifier' => (string) $group->translationSetIdentifier(),
                'editor_id' => (string) $group->editorIdentifier(),
                'translation' => $group->translation()->value,
                'name' => (string) $group->name(),
                'agency_id' => $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
                'description' => (string) $group->description(),
                'song_identifiers' => $this->extractSongIdentifiers($group->songIdentifiers()),
                'image_path' => $group->imagePath() ? (string) $group->imagePath() : null,
                'status' => $group->status()->value,
                'version' => null,
            ],
        );
    }

    public function deleteDraft(DraftGroup $group): void
    {
        GroupModel::query()
            ->where('id', (string) $group->groupIdentifier())
            ->whereNotNull('editor_id')
            ->delete();
    }

    public function findDraftsByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $draftModels = GroupModel::query()
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->whereNotNull('editor_id')
            ->get();

        return $draftModels
            ->map(fn (GroupModel $model): DraftGroup => $this->mapDraftEntity($model))
            ->toArray();
    }

    /**
     * @param array<int, string>|null $songIdentifiers
     * @return SongIdentifier[]
     */
    private function mapSongIdentifiers(?array $songIdentifiers): array
    {
        $identifiers = $songIdentifiers ?? [];

        return array_map(
            static fn (string $songId): SongIdentifier => new SongIdentifier($songId),
            $identifiers,
        );
    }

    /**
     * @param SongIdentifier[] $songIdentifiers
     * @return string[]
     */
    private function extractSongIdentifiers(array $songIdentifiers): array
    {
        return array_map(
            static fn (SongIdentifier $identifier): string => (string) $identifier,
            $songIdentifiers,
        );
    }

    private function mapGroupEntity(GroupModel $model): Group
    {
        return new Group(
            new GroupIdentifier($model->id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Translation::from($model->translation),
            new GroupName($model->name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            $this->mapSongIdentifiers($model->song_identifiers),
            $model->image_path ? new ImagePath($model->image_path) : null,
            new Version($model->version ?? 1),
        );
    }

    private function mapDraftEntity(GroupModel $model): DraftGroup
    {
        return new DraftGroup(
            new GroupIdentifier($model->id),
            $model->published_id ? new GroupIdentifier($model->published_id) : null,
            new TranslationSetIdentifier($model->translation_set_identifier),
            new EditorIdentifier($model->editor_id),
            Translation::from($model->translation),
            new GroupName($model->name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            $this->mapSongIdentifiers($model->song_identifiers),
            $model->image_path ? new ImagePath($model->image_path) : null,
            ApprovalStatus::from($model->status),
        );
    }
}
