<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\Wiki as WikiModel;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentDraftWikiReadModel;

readonly class GetTalentDraftWiki implements GetTalentDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetTalentDraftWikiInputPort $input): TalentDraftWikiReadModel
    {
        $model = DraftWikiModel::query()
            ->with(['talentBasic.groups.groupBasic', 'publishedWiki'])
            ->where('resource_type', ResourceType::TALENT->value)
            ->where('language', $input->language()->value)
            ->where('slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $model->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for DraftWiki.');
        }

        return new TalentDraftWikiReadModel(
            wikiIdentifier: $model->id,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::TALENT->value,
            version: $model->publishedWiki->version,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $basic->profile_image_identifier,
            ],
            basic: [
                'name' => $basic->name,
                'normalizedName' => $basic->normalized_name,
                'realName' => $basic->real_name,
                'normalizedRealName' => $basic->normalized_real_name,
                'birthday' => $basic->birthday,
                'agencyIdentifier' => $basic->agency_identifier,
                'emoji' => $basic->emoji,
                'representativeSymbol' => $basic->representative_symbol,
                'position' => $basic->position,
                'mbti' => $basic->mbti,
                'zodiacSign' => $basic->zodiac_sign,
                'englishLevel' => $basic->english_level,
                'height' => $basic->height,
                'bloodType' => $basic->blood_type,
                'fandomName' => $basic->fandom_name,
                'profileImageIdentifier' => $basic->profile_image_identifier,
                'groups' => $basic->groups->map(fn (WikiModel $group) => $this->groupToArray($group))->values()->all(),
            ],
            sections: $model->sections,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function groupToArray(WikiModel $group): array
    {
        $basic = $group->groupBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for Wiki.');
        }

        return [
            'wikiIdentifier' => $group->id,
            'slug' => $group->slug,
            'language' => $group->language,
            'name' => $basic->name,
            'normalizedName' => $basic->normalized_name,
            'agencyIdentifier' => $basic->agency_identifier,
            'groupType' => $basic->group_type,
            'status' => $basic->status,
            'generation' => $basic->generation,
            'debutDate' => $basic->debut_date,
            'disbandDate' => $basic->disband_date,
            'fandomName' => $basic->fandom_name,
            'officialColors' => $basic->official_colors,
            'emoji' => $basic->emoji,
            'representativeSymbol' => $basic->representative_symbol,
            'mainImageIdentifier' => $basic->main_image_identifier,
        ];
    }
}
