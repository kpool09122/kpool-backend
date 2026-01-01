<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Group as GroupModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
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

        return $this->toEntity($groupModel);
    }

    public function save(Group $group): void
    {
        GroupModel::query()->updateOrCreate(
            [
               'id' => (string)$group->groupIdentifier(),
            ],
            [
               'translation_set_identifier' => (string)$group->translationSetIdentifier(),
               'translation' => $group->language()->value,
               'name' => (string)$group->name(),
               'normalized_name' => $group->normalizedName(),
               'agency_id' => $group->agencyIdentifier() ? (string)$group->agencyIdentifier() : null,
               'description' => (string)$group->description(),
               'image_path' => $group->imagePath() ? (string)$group->imagePath() : null,
               'version' => $group->version()->value(),
            ],
        );
    }

    private function toEntity(GroupModel $model): Group
    {
        return new Group(
            new GroupIdentifier($model->id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            Language::from($model->translation),
            new GroupName($model->name),
            $model->normalized_name,
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            $model->image_path ? new ImagePath($model->image_path) : null,
            new Version($model->version ?? 1),
        );
    }
}
