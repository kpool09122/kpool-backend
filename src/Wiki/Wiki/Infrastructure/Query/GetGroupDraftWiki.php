<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\DraftWikiGroupBasic as DraftWikiGroupBasicModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;

readonly class GetGroupDraftWiki implements GetGroupDraftWikiInterface
{
    /**
     * @param GetGroupDraftWikiInputPort $input
     * @return DraftWikiReadModel
     * @throws WikiNotFoundException
     */
    public function process(GetGroupDraftWikiInputPort $input): DraftWikiReadModel
    {
        $model = DraftWikiModel::query()
            ->select('draft_wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['groupBasic', 'publishedWiki'])
            ->where('draft_wikis.resource_type', ResourceType::GROUP->value)
            ->where('draft_wikis.language', $input->language()->value)
            ->where('draft_wikis.slug', (string) $input->slug())
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft wiki not found for slug: {$input->slug()} and language: {$input->language()->value}");
        }

        $basic = $this->groupBasic($model->groupBasic);

        return new DraftWikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::GROUP->value,
            themeColor: $model->theme_color,
            heroImage: [
                'imageIdentifier' => $model->image_identifier,
                'src' => ImageUrl::fromPath($model->getAttribute('hero_image_path')),
                'alt' => $model->getAttribute('hero_image_alt_text'),
            ],
            basic: new GroupWikiBasicReadModel(
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
            ),
            sections: $this->sectionsWithImages($model->sections),
            status: $model->status,
        );
    }

    private function groupBasic(?DraftWikiGroupBasicModel $basic): DraftWikiGroupBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('GroupBasic not found for DraftWiki.');
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
