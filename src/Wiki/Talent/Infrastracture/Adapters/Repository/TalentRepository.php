<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastracture\Adapters\Repository;

use Application\Models\Wiki\DraftTalent as DraftTalentModel;
use Application\Models\Wiki\Group;
use Application\Models\Wiki\Talent as TalentModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

final class TalentRepository implements TalentRepositoryInterface
{
    public function findById(TalentIdentifier $identifier): ?Talent
    {
        $talentModel = TalentModel::query()
            ->with('groups')
            ->where('id', (string) $identifier)
            ->first();

        if ($talentModel === null || $talentModel->version === null) {
            return null;
        }

        $groupIdentifiers = $talentModel->groups
            ->map(fn (Group $group) => new GroupIdentifier($group->id))
            ->toArray();

        $relevantVideoLinks = RelevantVideoLinks::formStringArray($talentModel->relevant_video_links ?? []);

        return new Talent(
            new TalentIdentifier($talentModel->id),
            new TranslationSetIdentifier($talentModel->translation_set_identifier),
            Language::from($talentModel->language),
            new TalentName($talentModel->name),
            new RealName($talentModel->real_name),
            $talentModel->agency_id ? new AgencyIdentifier($talentModel->agency_id) : null,
            $groupIdentifiers,
            $talentModel->birthday ? new Birthday($talentModel->birthday->toDateTimeImmutable()) : null,
            new Career($talentModel->career),
            $talentModel->image_link ? new ImagePath($talentModel->image_link) : null,
            $relevantVideoLinks,
            new Version($talentModel->version ?? 1),
        );
    }

    public function save(Talent $talent): void
    {
        $birthday = $talent->birthday();
        $birthdayValue = $birthday?->format('Y-m-d');

        /** @var TalentModel $talentModel */
        $talentModel = TalentModel::query()->updateOrCreate(
            [
                'id' => (string) $talent->talentIdentifier(),
            ],
            [
                'translation_set_identifier' => (string) $talent->translationSetIdentifier(),
                'language' => $talent->language()->value,
                'name' => (string) $talent->name(),
                'real_name' => (string) $talent->realName(),
                'agency_id' => $talent->agencyIdentifier() ? (string) $talent->agencyIdentifier() : null,
                'birthday' => $birthdayValue,
                'career' => (string) $talent->career(),
                'image_link' => $talent->imageLink() ? (string) $talent->imageLink() : null,
                'relevant_video_links' => $talent->relevantVideoLinks()->toStringArray(),
                'version' => $talent->version()->value(),
            ],
        );

        $groupIds = array_map(
            static fn (GroupIdentifier $identifier): string => (string) $identifier,
            $talent->groupIdentifiers(),
        );
        $talentModel->groups()->sync($groupIds);
    }

    public function findDraftById(TalentIdentifier $identifier): ?DraftTalent
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
            new RealName($draftModel->real_name),
            $draftModel->agency_id ? new AgencyIdentifier($draftModel->agency_id) : null,
            $groupIdentifiers,
            $draftModel->birthday ? new Birthday($draftModel->birthday->toDateTimeImmutable()) : null,
            new Career($draftModel->career),
            $draftModel->image_link ? new ImagePath($draftModel->image_link) : null,
            $relevantVideoLinks,
            ApprovalStatus::from($draftModel->status),
        );
    }

    public function saveDraft(DraftTalent $talent): void
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
                'real_name' => (string) $talent->realName(),
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

    public function deleteDraft(DraftTalent $talent): void
    {
        DraftTalentModel::query()
            ->where('id', (string) $talent->talentIdentifier())
            ->delete();
    }

    public function findDraftsByTranslationSet(
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
                new RealName($model->real_name),
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
