<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiGroupSummaryReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

readonly class GetTalentWiki implements GetTalentWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetTalentWikiInputPort $input): WikiReadModel
    {
        $model = WikiModel::query()
            ->select('wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->with(['talentBasic.groups.groupBasic'])
            ->where('wikis.resource_type', ResourceType::TALENT->value)
            ->where('wikis.language', $input->language()->value)
            ->where('wikis.slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $model->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for Wiki.');
        }

        return new WikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::TALENT->value,
            version: $model->version,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $model->image_identifier,
                'src' => ImageUrl::fromPath($model->getAttribute('hero_image_path')),
                'alt' => $model->getAttribute('hero_image_alt_text'),
            ],
            basic: new TalentWikiBasicReadModel(
                name: $basic->name,
                normalizedName: $basic->normalized_name,
                realName: $basic->real_name,
                normalizedRealName: $basic->normalized_real_name,
                birthday: $basic->birthday,
                agencyIdentifier: $basic->agency_identifier,
                emoji: $basic->emoji,
                representativeSymbol: $basic->representative_symbol,
                position: $basic->position,
                mbti: $basic->mbti,
                zodiacSign: $basic->zodiac_sign,
                englishLevel: $basic->english_level,
                height: $basic->height,
                bloodType: $basic->blood_type,
                fandomName: $basic->fandom_name,
                groups: $basic->groups->map(fn (WikiModel $group) => $this->groupSummary($group))->values()->all(),
            ),
            sections: $this->sectionsWithImages($model->sections),
        );
    }

    /**
     */
    private function groupSummary(WikiModel $group): TalentWikiGroupSummaryReadModel
    {
        $basic = $group->groupBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for Wiki.');
        }

        return new TalentWikiGroupSummaryReadModel(
            wikiIdentifier: $group->id,
            slug: $group->slug,
            language: $group->language,
            name: $basic->name,
            normalizedName: $basic->normalized_name,
            agencyIdentifier: $basic->agency_identifier,
            groupType: $basic->group_type,
            status: $basic->status,
            generation: $basic->generation,
            debutDate: $basic->debut_date,
            disbandDate: $basic->disband_date,
            fandomName: $basic->fandom_name,
            officialColors: $basic->official_colors,
            emoji: $basic->emoji,
            representativeSymbol: $basic->representative_symbol,
        );
    }

    /**
     * @param list<array<string, mixed>> $sections
     * @return list<array<string, mixed>>
     */
    private function sectionsWithImages(array $sections): array
    {
        return WikiSectionReadModelBuilder::build($sections);
    }
}
