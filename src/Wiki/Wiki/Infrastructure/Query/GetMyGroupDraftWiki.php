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
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyGroupDraftWiki\GetMyGroupDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyGroupDraftWiki\GetMyGroupDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;

readonly class GetMyGroupDraftWiki implements GetMyGroupDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMyGroupDraftWikiInputPort $input): DraftWikiReadModel
    {
        $slug = $input->slug();
        $language = $input->language();
        $editorIdentifier = $input->editorIdentifier();

        $model = DraftWikiModel::query()
            ->select('draft_wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['groupBasic', 'publishedWiki'])
            ->where('draft_wikis.resource_type', ResourceType::GROUP->value)
            ->where('draft_wikis.language', $language->value)
            ->where('draft_wikis.slug', (string) $slug)
            ->where('draft_wikis.editor_id', (string) $editorIdentifier)
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft group wiki not found for slug: {$slug}, language: {$language->value}, and editor: {$editorIdentifier}");
        }

        return $this->readModel($model);
    }

    private function readModel(DraftWikiModel $model): DraftWikiReadModel
    {
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
