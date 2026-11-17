<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki as DraftWikiModel;
use Application\Models\Wiki\DraftWikiGroupBasic as DraftWikiGroupBasicModel;
use InvalidArgumentException;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki\GetGroupDraftWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\OfficialColorReadModelMapper;

readonly class GetGroupDraftWiki implements GetGroupDraftWikiInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws WikiNotFoundException
     */
    public function process(GetGroupDraftWikiInputPort $input): DraftWikiReadModel
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $wikiIdentifier = $input->wikiIdentifier();
        $model = DraftWikiModel::query()
            ->select(
                'draft_wikis.*',
                'wiki_images.image_path as hero_image_path',
                'wiki_images.alt_text as hero_image_alt_text',
                'wiki_images.source_url as hero_image_source_url',
                'wiki_images.source_name as hero_image_source_name',
                'wiki_images.author as hero_image_author',
                'wiki_images.license as hero_image_license',
                'wiki_images.is_hidden as hero_image_is_hidden',
            )
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'draft_wikis.image_identifier')
            ->with(['groupBasic', 'publishedWiki'])
            ->where('draft_wikis.resource_type', ResourceType::GROUP->value)
            ->where('draft_wikis.id', (string) $wikiIdentifier)
            ->first();

        if ($model === null) {
            throw new WikiNotFoundException("Draft group wiki not found for wiki identifier: {$wikiIdentifier}");
        }

        $basic = $this->groupBasic($model->groupBasic);
        $resource = new Resource(
            type: ResourceType::GROUP,
            agencyId: $basic->agency_identifier,
            groupIds: [$model->id],
            editorId: $model->editor_id,
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::READ, $resource)) {
            throw new DisallowedException();
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
            fontStyle: $model->font_style,
            title: $model->title,
            metaDescription: $model->meta_description,
            keywords: $model->keywords,
            heroImage: [
                'imageIdentifier' => $model->image_identifier,
                'src' => ImageUrl::fromPath(TypedValue::nullableString($model->getAttribute('hero_image_path'))),
                'alt' => $model->getAttribute('hero_image_alt_text'),
                'sourceUrl' => $model->getAttribute('hero_image_source_url'),
                'sourceName' => $model->getAttribute('hero_image_source_name'),
                'author' => $model->getAttribute('hero_image_author'),
                'license' => $model->getAttribute('hero_image_license'),
                'isHidden' => $model->getAttribute('hero_image_is_hidden') === null
                    ? null
                    : (bool) $model->getAttribute('hero_image_is_hidden'),
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
                fandomName: TypedValue::string($basic->fandom_name),
                officialColors: OfficialColorReadModelMapper::toArray(array_values($basic->official_colors)),
                emoji: TypedValue::string($basic->emoji),
                representativeSymbol: TypedValue::string($basic->representative_symbol),
            ),
            sections: $this->sectionsWithImages(array_values($model->sections)),
            status: $model->status,
            rejectionReason: $model->rejection_reason,
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
     * @return list<array<array-key, mixed>>
     */
    private function sectionsWithImages(array $sections): array
    {
        return WikiSectionReadModelBuilder::build($sections);
    }
}
