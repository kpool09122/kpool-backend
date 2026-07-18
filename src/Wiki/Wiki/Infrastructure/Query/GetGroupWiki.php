<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiGroupBasic as WikiGroupBasicModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\OfficialColorReadModelMapper;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;

readonly class GetGroupWiki implements GetGroupWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetGroupWikiInputPort $input): WikiReadModel
    {
        $model = WikiModel::query()
            ->select('wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->with(['groupBasic'])
            ->where('wikis.resource_type', ResourceType::GROUP->value)
            ->where('wikis.language', $input->language()->value)
            ->where('wikis.slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $this->groupBasic($model->groupBasic);

        return new WikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::GROUP->value,
            version: $model->version,
            themeColor: $model->theme_color,
            fontStyle: $model->font_style,
            title: $model->title,
            metaDescription: $model->meta_description,
            keywords: $model->keywords,
            heroImage: [
                'imageIdentifier' => $model->image_identifier,
                'src' => ImageUrl::fromPath($model->getAttribute('hero_image_path')),
                'alt' => $model->getAttribute('hero_image_alt_text'),
            ],
            basic: new GroupWikiBasicReadModel(
                name: $basic->name,
                normalizedName: $basic->normalized_name,
                agencyIdentifier: $basic->agency_identifier,
                agency: WikiAgencySummaryResolver::resolve($basic->agency_identifier),
                groupType: $basic->group_type,
                status: $basic->status,
                generation: $basic->generation,
                debutDate: $basic->debut_date,
                disbandDate: $basic->disband_date,
                fandomName: $basic->fandom_name,
                officialColors: OfficialColorReadModelMapper::toArray($basic->official_colors),
                emoji: $basic->emoji,
                representativeSymbol: $basic->representative_symbol,
            ),
            sections: $this->sectionsWithImages($model->sections),
        );
    }

    private function groupBasic(?WikiGroupBasicModel $basic): WikiGroupBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for Wiki.');
        }

        return $basic;
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
