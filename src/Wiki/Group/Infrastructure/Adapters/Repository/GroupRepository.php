<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Group as GroupModel;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
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

    /**
     * @return Group[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array
    {
        $groupModels = GroupModel::query()
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->whereNotNull('version')
            ->get();

        return $groupModels->map(fn (GroupModel $model) => $this->toEntity($model))->toArray();
    }

    public function existsBySlug(Slug $slug): bool
    {
        return GroupModel::query()
            ->where('slug', (string) $slug)
            ->exists();
    }

    public function save(Group $group): void
    {
        GroupModel::query()->updateOrCreate(
            [
               'id' => (string)$group->groupIdentifier(),
            ],
            [
               'translation_set_identifier' => (string)$group->translationSetIdentifier(),
               'slug' => (string)$group->slug(),
               'translation' => $group->language()->value,
               'name' => (string)$group->name(),
               'normalized_name' => $group->normalizedName(),
               'agency_id' => $group->agencyIdentifier() ? (string)$group->agencyIdentifier() : null,
               'description' => (string)$group->description(),
               'editor_id' => $group->editorIdentifier() ? (string) $group->editorIdentifier() : null,
               'approver_id' => $group->approverIdentifier() ? (string) $group->approverIdentifier() : null,
               'merger_id' => $group->mergerIdentifier() ? (string) $group->mergerIdentifier() : null,
               'merged_at' => $group->mergedAt(),
               'source_editor_id' => $group->sourceEditorIdentifier() ? (string) $group->sourceEditorIdentifier() : null,
               'translated_at' => $group->translatedAt(),
               'approved_at' => $group->approvedAt(),
               'version' => $group->version()->value(),
               'is_official' => $group->isOfficial(),
               'owner_account_id' => $group->ownerAccountIdentifier() ? (string) $group->ownerAccountIdentifier() : null,
            ],
        );
    }

    private function toEntity(GroupModel $model): Group
    {
        return new Group(
            new GroupIdentifier($model->id),
            new TranslationSetIdentifier($model->translation_set_identifier),
            new Slug($model->slug),
            Language::from($model->translation),
            new GroupName($model->name),
            $model->normalized_name,
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            new Version($model->version),
            $model->merger_id ? new PrincipalIdentifier($model->merger_id) : null,
            $model->merged_at?->toDateTimeImmutable(),
            $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
            (bool) $model->is_official,
            $model->owner_account_id ? new AccountIdentifier($model->owner_account_id) : null,
            $model->source_editor_id ? new PrincipalIdentifier($model->source_editor_id) : null,
            $model->translated_at?->toDateTimeImmutable(),
            $model->approved_at?->toDateTimeImmutable(),
        );
    }
}
