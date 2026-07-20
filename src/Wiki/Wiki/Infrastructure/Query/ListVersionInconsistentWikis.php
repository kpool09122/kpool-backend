<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Application\Models\Wiki\WikiAgencyBasic;
use Application\Models\Wiki\WikiGroupBasic;
use Application\Models\Wiki\WikiSongBasic;
use Application\Models\Wiki\WikiTalentBasic;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Infrastructure\Support\ImageUrl;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

readonly class ListVersionInconsistentWikis implements ListVersionInconsistentWikisInterface
{
    /** @var array<string, array{relation: string, table: string}> */
    private const BASIC_TABLES = [
        ResourceType::TALENT->value => ['relation' => 'talentBasic', 'table' => 'wiki_talent_basics'],
        ResourceType::GROUP->value => ['relation' => 'groupBasic', 'table' => 'wiki_group_basics'],
        ResourceType::AGENCY->value => ['relation' => 'agencyBasic', 'table' => 'wiki_agency_basics'],
        ResourceType::SONG->value => ['relation' => 'songBasic', 'table' => 'wiki_song_basics'],
    ];

    public function process(
        ListVersionInconsistentWikisInputPort $input,
        ListVersionInconsistentWikisOutputPort $output,
    ): void {
        $inconsistentSets = DB::table('wikis')
            ->select('translation_set_identifier', DB::raw('MAX(version) as latest_version'))
            ->whereIn('resource_type', $this->targetResourceTypes($input->resourceType()))
            ->groupBy('translation_set_identifier')
            ->havingRaw(
                'MIN(version) != MAX(version) OR COUNT(DISTINCT language) < ?',
                [count(Language::cases())],
            );

        $query = WikiModel::query()
            ->select('wikis.*', 'wiki_images.image_path as image_path', 'wiki_images.alt_text as image_alt_text', 'wiki_images.is_hidden as image_is_hidden')
            ->joinSub($inconsistentSets, 'version_inconsistent_sets', function ($join): void {
                $join->on('version_inconsistent_sets.translation_set_identifier', '=', 'wikis.translation_set_identifier')
                    ->on('version_inconsistent_sets.latest_version', '=', 'wikis.version');
            })
            ->leftJoin('wiki_images', 'wiki_images.id', '=', 'wikis.image_identifier')
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic'])
            ->whereIn('wikis.resource_type', $this->targetResourceTypes($input->resourceType()));

        $this->joinBasicTables($query);
        $this->applySort($query, $input->sort(), $input->order());

        /** @var LengthAwarePaginator<int, WikiModel> $paginator */
        $paginator = $query->paginate($input->perPage());

        $output->output(
            array_map(
                fn (WikiModel $wiki): WikiListItemReadModel => $this->toReadModel($wiki),
                $paginator->items(),
            ),
            $paginator->currentPage(),
            $paginator->lastPage(),
            $paginator->total(),
            $paginator->perPage(),
        );
    }

    /**
     * @return list<string>
     */
    private function targetResourceTypes(?ResourceType $resourceType): array
    {
        if ($resourceType !== null) {
            return [$resourceType->value];
        }

        return array_keys(self::BASIC_TABLES);
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function joinBasicTables(Builder $query): void
    {
        foreach (self::BASIC_TABLES as $basic) {
            $query->leftJoin($basic['table'], "{$basic['table']}.wiki_id", '=', 'wikis.id');
        }
    }

    /**
     * @param Builder<WikiModel> $query
     */
    private function applySort(Builder $query, string $sort, string $order): void
    {
        if ($sort === 'name') {
            $query->orderBy(DB::raw($this->nameSortExpression()), $order)
                ->orderBy('wikis.updated_at', 'desc');

            return;
        }

        $query->orderBy('wikis.updated_at', $order);
    }

    private function nameSortExpression(): string
    {
        return 'COALESCE(wiki_talent_basics.name, wiki_group_basics.name, wiki_agency_basics.name, wiki_song_basics.name)';
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
