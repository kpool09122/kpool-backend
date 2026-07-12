<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\DraftWikiAgencyBasic as DraftWikiAgencyBasicModel;
use InvalidArgumentException;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\AgencyWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiInterface;

readonly class GetMyAgencyDraftWiki implements GetMyAgencyDraftWikiInterface
{
    /**
     * @throws WikiNotFoundException
     */
    public function process(GetMyAgencyDraftWikiInputPort $input): DraftWikiReadModel
    {
        $slug = $input->slug();
        $language = $input->language();
        $editorIdentifier = $input->editorIdentifier();

        $model = DraftWikiModel::query()
            ->select('draft_wikis.*', 'wiki_images.image_path as hero_image_path', 'wiki_images.alt_text as hero_image_alt_text')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['agencyBasic', 'publishedWiki'])
            ->where('draft_wikis.resource_type', ResourceType::AGENCY->value)
            ->where('draft_wikis.language', $language->value)
            ->where('draft_wikis.slug', (string) $slug)
            ->where('draft_wikis.editor_id', (string) $editorIdentifier)
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft agency wiki not found for slug: {$slug}, language: {$language->value}, and editor: {$editorIdentifier}");
        }

        return $this->readModel($model);
    }

    private function readModel(DraftWikiModel $model): DraftWikiReadModel
    {
        $basic = $this->agencyBasic($model->agencyBasic);

        return new DraftWikiReadModel(
            wikiIdentifier: $model->id,
            translationSetIdentifier: $model->translation_set_identifier,
            slug: $model->slug,
            language: $model->language,
            resourceType: ResourceType::AGENCY->value,
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
            basic: new AgencyWikiBasicReadModel(
                name: $basic->name,
                normalizedName: $basic->normalized_name,
                ceo: $basic->ceo,
                normalizedCeo: $basic->normalized_ceo,
                foundedIn: $basic->founded_in,
                parentAgencyIdentifier: $basic->parent_agency_identifier,
                status: $basic->status,
                officialWebsite: $basic->official_website,
                socialLinks: $basic->social_links,
            ),
            sections: $this->sectionsWithImages($model->sections),
            status: $model->status,
            rejectionReason: $model->rejection_reason,
        );
    }

    private function agencyBasic(?DraftWikiAgencyBasicModel $basic): DraftWikiAgencyBasicModel
    {
        if ($basic === null) {
            throw new InvalidArgumentException('AgencyBasic not found for DraftWiki.');
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
