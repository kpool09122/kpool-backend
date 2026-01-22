<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\DraftGroup as DraftGroupModel;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class DraftGroupRepository implements DraftGroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?DraftGroup
    {
        $draftModel = DraftGroupModel::query()
            ->where('id', (string) $groupIdentifier)
            ->first();

        if ($draftModel === null) {
            return null;
        }

        return $this->toEntity($draftModel);
    }

    public function save(DraftGroup $group): void
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
                'editor_id' => $group->editorIdentifier() ? (string) $group->editorIdentifier() : null,
                'translation' => $group->language()->value,
                'name' => (string) $group->name(),
                'normalized_name' => $group->normalizedName(),
                'agency_id' => $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
                'description' => (string) $group->description(),
                'status' => $group->status()->value,
                'approver_id' => $group->approverIdentifier() ? (string) $group->approverIdentifier() : null,
            ],
        );
    }

    public function delete(DraftGroup $group): void
    {
        DraftGroupModel::query()
            ->where('id', (string) $group->groupIdentifier())
            ->delete();
    }

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftGroup[]
     */
    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $draftModels = DraftGroupModel::query()
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->get();

        return $draftModels
            ->map(fn (DraftGroupModel $model): DraftGroup => $this->toEntity($model))
            ->toArray();
    }

    private function toEntity(DraftGroupModel $model): DraftGroup
    {
        return new DraftGroup(
            new GroupIdentifier($model->id),
            $model->published_id ? new GroupIdentifier($model->published_id) : null,
            new TranslationSetIdentifier($model->translation_set_identifier),
            $model->editor_id ? new PrincipalIdentifier($model->editor_id) : null,
            Language::from($model->translation),
            new GroupName($model->name),
            $model->normalized_name,
            $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
            new Description($model->description),
            ApprovalStatus::from($model->status),
            $model->approver_id ? new PrincipalIdentifier($model->approver_id) : null,
        );
    }
}
