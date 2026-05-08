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
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListWikis\ListWikisOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

readonly class ListWikis implements ListWikisInterface
{
    /** @var array<string, array{relation: string, table: string}> */
    private const BASIC_TABLES = [
        ResourceType::TALENT->value => ['relation' => 'talentBasic', 'table' => 'wiki_talent_basics'],
        ResourceType::GROUP->value => ['relation' => 'groupBasic', 'table' => 'wiki_group_basics'],
        ResourceType::AGENCY->value => ['relation' => 'agencyBasic', 'table' => 'wiki_agency_basics'],
        ResourceType::SONG->value => ['relation' => 'songBasic', 'table' => 'wiki_song_basics'],
    ];

    public function process(ListWikisInputPort $input, ListWikisOutputPort $output): void
    {
        $query = WikiModel::query()
            ->select('wikis.*')
            ->with(['talentBasic', 'groupBasic', 'agencyBasic', 'songBasic']);

        $this->joinBasicTables($query);

        if ($input->resourceType() !== null) {
            $query->where('wikis.resource_type', $input->resourceType());
        } else {
            $query->whereIn('wikis.resource_type', array_keys(self::BASIC_TABLES));
        }

        if ($input->keyword() !== null && $input->keyword() !== '') {
            $this->applyKeywordSearch($query, $input->keyword(), $input->resourceType());
        }

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
    private function applyKeywordSearch(Builder $query, string $keyword, ?string $resourceType): void
    {
        $query->where(function (Builder $query) use ($keyword, $resourceType): void {
            foreach ($this->targetBasicTables($resourceType) as $type => $basic) {
                $query->orWhere(function (Builder $query) use ($type, $basic, $keyword): void {
                    $query->where('wikis.resource_type', $type)
                        ->where("{$basic['table']}.normalized_name", 'like', $keyword . '%');
                });
            }
        });
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

    /**
     * @return array<string, array{relation: string, table: string}>
     */
    private function targetBasicTables(?string $resourceType): array
    {
        if ($resourceType === null) {
            return self::BASIC_TABLES;
        }

        return [$resourceType => self::BASIC_TABLES[$resourceType]];
    }

    private function toReadModel(WikiModel $wiki): WikiListItemReadModel
    {
        $basic = $this->basicModel($wiki);

        return new WikiListItemReadModel(
            wikiIdentifier: $wiki->id,
            slug: $wiki->slug,
            language: $wiki->language,
            resourceType: $wiki->resource_type,
            version: $wiki->version,
            themeColor: $wiki->theme_color,
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
}
