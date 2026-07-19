<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\Wiki as WikiModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyTalentDraftWiki\GetMyTalentDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyTalentDraftWiki\GetMyTalentDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\OfficialColorReadModelMapper;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiGroupSummaryReadModel;

readonly class GetMyTalentDraftWiki implements GetMyTalentDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMyTalentDraftWikiInputPort $input): DraftWikiReadModel
    {
        $slug = $input->slug();
        $language = $input->language();
        $editorIdentifier = $input->editorIdentifier();

        $model = DraftWikiModel::query()
            ->select(
                'draft_wikis.*',
                'wiki_images.image_path as hero_image_path',
                'wiki_images.alt_text as hero_image_alt_text',
                'wiki_images.is_hidden as hero_image_is_hidden',
            )
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['talentBasic.groups.groupBasic', 'publishedWiki'])
            ->where('draft_wikis.resource_type', ResourceType::TALENT->value)
            ->where('draft_wikis.language', $language->value)
            ->where('draft_wikis.slug', (string) $slug)
            ->where('draft_wikis.editor_id', (string) $editorIdentifier)
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft talent wiki not found for slug: {$slug}, language: {$language->value}, and editor: {$editorIdentifier}");
        }

        return $this->readModel($model);
    }

    private function readModel(DraftWikiModel $model): DraftWikiReadModel
    {
        $basic = $model->talentBasic;
        if ($basic === null) {
            throw new InvalidArgumentException('TalentBasic not found for DraftWiki.');
        }

        return new DraftWikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::TALENT->value,
            themeColor: $model->theme_color,
            fontStyle: $model->font_style,
            title: $model->title,
            metaDescription: $model->meta_description,
            keywords: $model->keywords,
            heroImage: [
                'imageIdentifier' => $model->image_identifier,
                'src' => ImageUrl::fromPath($model->getAttribute('hero_image_path')),
                'alt' => $model->getAttribute('hero_image_alt_text'),
                'isHidden' => $model->getAttribute('hero_image_is_hidden') === null
                    ? null
                    : (bool) $model->getAttribute('hero_image_is_hidden'),
            ],
            basic: new TalentWikiBasicReadModel(
                name: $basic->name,
                normalizedName: $basic->normalized_name,
                realName: $basic->real_name,
                normalizedRealName: $basic->normalized_real_name,
                birthday: $basic->birthday,
                agencyIdentifier: $basic->agency_identifier,
                agency: WikiAgencySummaryResolver::resolve($basic->agency_identifier),
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
            status: $model->status,
            rejectionReason: $model->rejection_reason,
        );
    }

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
            officialColors: OfficialColorReadModelMapper::toArray($basic->official_colors),
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
