<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Adapters\Repository;

use Application\Models\Wiki\Group;
use Application\Models\Wiki\Talent as TalentModel;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
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

        return $this->toEntity($talentModel);
    }

    /**
     * @return Talent[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array
    {
        $talentModels = TalentModel::query()
            ->with('groups')
            ->where('translation_set_identifier', (string) $translationSetIdentifier)
            ->whereNotNull('version')
            ->get();

        return $talentModels->map(fn (TalentModel $model) => $this->toEntity($model))->toArray();
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

    private function toEntity(TalentModel $talentModel): Talent
    {
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
            new Version($talentModel->version),
        );
    }
}
