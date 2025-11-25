<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastracture\Adapters\Repository;

use Application\Models\Wiki\DraftGroup as DraftGroupModel;
use Application\Models\Wiki\Group as GroupModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
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
            ->first();

        if ($groupModel === null || $groupModel->version === null) {
            return null;
        }

        return $this->mapGroupEntity($groupModel);
    }

    public function findDraftById(GroupIdentifier $groupIdentifier): ?DraftGroup
    {
        $draftModel = DraftGroupModel::query()
            ->where('id', (string) $groupIdentifier)
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
                'translation_set_identifier' => (string) $group->translationSetIdentifier(),
                'translation' => $group->language()->value,
                'name' => (string) $group->name(),
                'agency_id' => $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
                'description' => (string) $group->description(),
                'song_identifiers' => $this->fromSongIdentifiers($group->songIdentifiers()),
                'image_path' => $group->imagePath() ? (string) $group->imagePath() : null,
                'version' => $group->version()->value(),
            ],
        );
    }

    public function saveDraft(DraftGroup $group): void
    {
        DraftGroupModel::query()->updateOrCreate(
            [
                'id' => (string) $group->groupIdentifier(),
            ],
            [
                'published_id' => $group->publishedGroupIdentifier()
                    ? (string) $group->publishedGroupIdentifier()
                    : null,
                'translation_set_identifier' => (string) $group->translationSetIdentifier(),
                'editor_id' => (string) $group->editorIdentifier(),
                'translation' => $group->language()->value,
                'name' => (string) $group->name(),
                'agency_id' => $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
                'description' => (string) $group->description(),
                'song_identifiers' => $this->fromSongIdentifiers($group->songIdentifiers()),
                'image_path' => $group->imagePath() ? (string) $group->imagePath() : null,
                'status' => $group->status()->value,
            ],
        );
    }

    public function deleteDraft(DraftGroup $group): void
    {
        DraftGroupModel::query()
            ->where('id', (string) $group->groupIdentifier())
            ->delete();
    }

    public function findDraftsByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $draftModels = DraftGroupModel::query()
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->get();

        return $draftModels
            ->map(fn (DraftGroupModel $model): DraftGroup => $this->mapDraftEntity($model))
            ->toArray();
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

    private function mapGroupEntity(GroupModel $model): Group
    {
        return new Group(
            new GroupIdentifier($model->id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->translation),
            new GroupName($model->name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            $this->toSongIdentifiers($model->song_identifiers),
            $model->image_path ? new ImagePath($model->image_path) : null,
            new Version($model->version ?? 1),
        );
    }

    private function mapDraftEntity(DraftGroupModel $model): DraftGroup
    {
        return new DraftGroup(
            new GroupIdentifier($model->id),
            $model->published_id ? new GroupIdentifier($model->published_id) : null,
            new TranslationSetIdentifier($model->translation_set_identifier),
            new EditorIdentifier($model->editor_id),
            Language::from($model->translation),
            new GroupName($model->name),
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            $this->toSongIdentifiers($model->song_identifiers),
            $model->image_path ? new ImagePath($model->image_path) : null,
            ApprovalStatus::from($model->status),
        );
    }
}
