<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic;
use Application\Models\Wiki\WikiGroupBasic;
use Application\Models\Wiki\WikiSongBasic;
use Application\Models\Wiki\WikiTalentBasic;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

readonly class ListRelatedWikis implements ListRelatedWikisInterface
{
    /** @var array<string, array{relation: string, table: string}> */
    private const BASIC_TABLES = [
        ResourceType::TALENT->value => ['relation' => 'talentBasic', 'table' => 'wiki_talent_basics'],
        ResourceType::GROUP->value => ['relation' => 'groupBasic', 'table' => 'wiki_group_basics'],
        ResourceType::AGENCY->value => ['relation' => 'agencyBasic', 'table' => 'wiki_agency_basics'],
        ResourceType::SONG->value => ['relation' => 'songBasic', 'table' => 'wiki_song_basics'],
    ];

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
    public function process(ListRelatedWikisInputPort $input, ListRelatedWikisOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        /** @var Collection<int, WikiModel> $sources */
        $sources = WikiModel::query()
            ->with(['agencyBasic', 'talentBasic.groups'])
            ->where('translation_set_identifier', (string) $input->translationSetIdentifier())
            ->where('resource_type', $input->resourceType()->value)
            ->whereNotNull('published_at')
            ->get();

        if ($sources->isEmpty()) {
            throw new WikiNotFoundException('Source wiki not found.');
        }

        foreach ($sources as $source) {
            $this->authorize($principal, $source);
        }

        $targetTypes = $this->targetTypes($input->resourceType(), $input->accountCategory());
        if ($targetTypes === []) {
            $output->output([]);

            return;
        }

        $sourceIds = $sources->map(static fn (WikiModel $wiki): string => $wiki->id)->all();

        $query = WikiModel::query()
            ->select('wikis.*', 'wiki_images.image_path as image_path', 'wiki_images.alt_text as image_alt_text', 'wiki_images.is_hidden as image_is_hidden')
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->whereNotNull('wikis.published_at')
            ->whereIn('wikis.resource_type', array_map(static fn (ResourceType $type): string => $type->value, $targetTypes));
        $this->joinBasicTables($query);
        $this->applyAgencyRelatedCondition($query, $sourceIds);

        $wikis = $query
            ->orderByRaw($this->nameSortExpression())
            ->orderBy('wikis.id')
            ->get()
            ->map(fn (WikiModel $wiki): WikiListItemReadModel => $this->toReadModel($wiki))
            ->values()
            ->all();

        $output->output($wikis);
    }

    private function authorize(Principal $principal, WikiModel $source): void
    {
        if (! $this->policyEvaluator->evaluate($principal, Action::RELATED_WIKI_LIST, $this->toResource($source))) {
            throw new DisallowedException();
        }
    }

    /** @return list<ResourceType> */
    private function targetTypes(ResourceType $sourceType, AccountCategory $accountCategory): array
    {
        if ($sourceType === ResourceType::AGENCY) {
            return $accountCategory === AccountCategory::AGENCY
                ? [ResourceType::GROUP, ResourceType::SONG]
                : [];
        }

        if ($sourceType === ResourceType::TALENT) {
            return [];
        }

        return [];
    }

    /** @param Builder<WikiModel> $query */
    private function joinBasicTables(Builder $query): void
    {
        foreach (self::BASIC_TABLES as $basic) {
            $query->leftJoin($basic['table'], "{$basic['table']}.wiki_id", '=', 'wikis.id');
        }
    }

    /**
     * @param Builder<WikiModel> $query
     * @param list<string> $sourceIds
     */
    private function applyAgencyRelatedCondition(Builder $query, array $sourceIds): void
    {
        $query->where(function (Builder $query) use ($sourceIds): void {
            $query->orWhere(function (Builder $query) use ($sourceIds): void {
                $query->where('wikis.resource_type', ResourceType::GROUP->value)
                    ->whereIn('wiki_group_basics.agency_identifier', $sourceIds);
            })->orWhere(function (Builder $query) use ($sourceIds): void {
                $query->where('wikis.resource_type', ResourceType::SONG->value)
                    ->whereIn('wiki_song_basics.agency_identifier', $sourceIds);
            })->orWhere(function (Builder $query) use ($sourceIds): void {
                $query->where('wikis.resource_type', ResourceType::TALENT->value)
                    ->whereIn('wiki_talent_basics.agency_identifier', $sourceIds);
            });
        });
    }

    private function nameSortExpression(): string
    {
        return 'COALESCE(wiki_talent_basics.name, wiki_group_basics.name, wiki_agency_basics.name, wiki_song_basics.name)';
    }

    private function toResource(WikiModel $wiki): Resource
    {
        $resourceType = ResourceType::from($wiki->resource_type);

        if ($resourceType === ResourceType::AGENCY) {
            return new Resource(type: $resourceType, agencyId: $wiki->id, editorId: $wiki->editor_id);
        }

        if ($resourceType === ResourceType::TALENT) {
            $basic = $wiki->talentBasic;
            if (! $basic instanceof WikiTalentBasic) {
                throw new InvalidArgumentException("Talent basic not found for Wiki: {$wiki->id}");
            }

            return new Resource(
                type: $resourceType,
                agencyId: $basic->agency_identifier,
                groupIds: $basic->groups->map(static fn (Model $group): string => (string) $group->id)->all(),
                talentIds: [$wiki->id],
                editorId: $wiki->editor_id,
            );
        }

        throw new InvalidArgumentException("Unsupported source wiki resource type: {$wiki->resource_type}");
    }

    private function toReadModel(WikiModel $wiki): WikiListItemReadModel
    {
        $basic = $this->basicModel($wiki);

        return new WikiListItemReadModel(
            wikiIdentifier: $wiki->id,
            translationSetIdentifier: $wiki->translation_set_identifier,
            slug: $wiki->slug,
            language: $wiki->language,
            resourceType: $wiki->resource_type,
            version: $wiki->version,
            themeColor: $wiki->theme_color,
            fontStyle: $wiki->font_style,
            title: $wiki->title,
            metaDescription: $wiki->meta_description,
            keywords: $wiki->keywords,
            imageIdentifier: $wiki->image_identifier,
            imageUrl: ImageUrl::fromPath($wiki->getAttribute('image_path')),
            imageAltText: $wiki->getAttribute('image_alt_text'),
            isHidden: $this->nullableBool($wiki->getAttribute('image_is_hidden')),
            name: (string) $basic->getAttribute('name'),
            normalizedName: (string) $basic->getAttribute('normalized_name'),
            publishedAt: $this->formatDateTime($wiki->published_at),
            updatedAt: $this->formatDateTime($wiki->updated_at),
            isOfficial: $wiki->owner_account_id !== null,
        );
    }

    private function basicModel(WikiModel $wiki): Model
    {
        $relation = self::BASIC_TABLES[$wiki->resource_type]['relation'] ?? null;
        if ($relation === null) {
            throw new InvalidArgumentException("Unsupported wiki resource type: {$wiki->resource_type}");
        }

        $basic = $wiki->{$relation};
        if (
            ! $basic instanceof WikiTalentBasic
            && ! $basic instanceof WikiGroupBasic
            && ! $basic instanceof WikiAgencyBasic
            && ! $basic instanceof WikiSongBasic
        ) {
            throw new InvalidArgumentException("Basic not found for Wiki: {$wiki->id}");
        }

        return $basic;
    }

    private function formatDateTime(mixed $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        if ($dateTime instanceof DateTimeInterface) {
            return $dateTime->format(DateTimeInterface::ATOM);
        }

        return (string) $dateTime;
    }

    private function nullableBool(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }
}
