<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\DraftTalent as DraftTalentModel;
use Application\Models\Wiki\Group;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

final class DraftTalentRepository implements DraftTalentRepositoryInterface
{
    public function findById(TalentIdentifier $identifier): ?DraftTalent
    {
        $draftModel = DraftTalentModel::query()
            ->with('groups')
            ->where('id', (string) $identifier)
            ->first();

        if ($draftModel === null) {
            return null;
        }

        $groupIdentifiers = $draftModel->groups
            ->map(fn (Group $group) => new GroupIdentifier($group->id))
            ->toArray();

        $relevantVideoLinks = RelevantVideoLinks::formStringArray($draftModel->relevant_video_links ?? []);

        return new DraftTalent(
            new TalentIdentifier($draftModel->id),
            $draftModel->published_id ? new TalentIdentifier($draftModel->published_id) : null,
            new TranslationSetIdentifier($draftModel->translation_set_identifier),
            new PrincipalIdentifier($draftModel->editor_id),
            Language::from($draftModel->language),
            new TalentName($draftModel->name),
            $draftModel->normalized_name,
            new RealName($draftModel->real_name),
            $draftModel->normalized_real_name,
            $draftModel->agency_id ? new AgencyIdentifier($draftModel->agency_id) : null,
            $groupIdentifiers,
            $draftModel->birthday ? new Birthday($draftModel->birthday->toDateTimeImmutable()) : null,
            new Career($draftModel->career),
            $draftModel->image_link ? new ImagePath($draftModel->image_link) : null,
            $relevantVideoLinks,
            ApprovalStatus::from($draftModel->status),
        );
    }

    public function save(DraftTalent $talent): void
    {
        $birthday = $talent->birthday();
        $birthdayValue = $birthday?->format('Y-m-d');

        /** @var DraftTalentModel $draftModel */
        $draftModel = DraftTalentModel::query()->updateOrCreate(
            [
                'id' => (string) $talent->talentIdentifier(),
            ],
            [
                'published_id' => $talent->publishedTalentIdentifier()
                    ? (string) $talent->publishedTalentIdentifier()
                    : null,
                'translation_set_identifier' => (string) $talent->translationSetIdentifier(),
                'editor_id' => (string) $talent->editorIdentifier(),
                'language' => $talent->language()->value,
                'name' => (string) $talent->name(),
                'normalized_name' => $talent->normalizedName(),
                'real_name' => (string) $talent->realName(),
                'normalized_real_name' => $talent->normalizedRealName(),
                'agency_id' => $talent->agencyIdentifier() ? (string) $talent->agencyIdentifier() : null,
                'birthday' => $birthdayValue,
                'career' => (string) $talent->career(),
                'image_link' => $talent->imageLink() ? (string) $talent->imageLink() : null,
                'relevant_video_links' => $talent->relevantVideoLinks()->toStringArray(),
                'status' => $talent->status()->value,
            ],
        );

        $groupIds = array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $talent->groupIdentifiers(),
        );
        $draftModel->groups()->sync($groupIds);
    }

    public function delete(DraftTalent $talent): void
    {
        DraftTalentModel::query()
            ->where('id', (string) $talent->talentIdentifier())
            ->delete();
    }

    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array {
        $draftModels = DraftTalentModel::query()
            ->with('groups')
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->get();

        $drafts = [];

        /** @var DraftTalentModel $model */
        foreach ($draftModels as $model) {
            $groupIdentifiers = $model->groups
                ->map(fn (Group $group) => new GroupIdentifier($group->id))
                ->toArray();

            $relevantVideoLinks = RelevantVideoLinks::formStringArray($model->relevant_video_links ?? []);

            $drafts[] = new DraftTalent(
                new TalentIdentifier($model->id),
                $model->published_id ? new TalentIdentifier($model->published_id) : null,
                new TranslationSetIdentifier($model->translation_set_identifier),
                new PrincipalIdentifier($model->editor_id),
                Language::from($model->language),
                new TalentName($model->name),
                $model->normalized_name,
                new RealName($model->real_name),
                $model->normalized_real_name,
                $model->agency_id ? new AgencyIdentifier($model->agency_id) : null,
                $groupIdentifiers,
                $model->birthday ? new Birthday($model->birthday->toDateTimeImmutable()) : null,
                new Career($model->career),
                $model->image_link ? new ImagePath($model->image_link) : null,
                $relevantVideoLinks,
                ApprovalStatus::from($model->status),
            );
        }

        return $drafts;
    }
}
