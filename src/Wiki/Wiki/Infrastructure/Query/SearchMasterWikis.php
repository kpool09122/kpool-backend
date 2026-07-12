<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Illuminate\Database\Eloquent\Builder;
use Source\Shared\Infrastructure\Trait\WhereLike;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiMasterSearchItemReadModel;

readonly class SearchMasterWikis implements SearchMasterWikisInterface
{
    use WhereLike;

    /** @var array<string, string> */
    private const BASIC_TABLES = [
        ResourceType::AGENCY->value => 'wiki_agency_basics',
        ResourceType::GROUP->value => 'wiki_group_basics',
        ResourceType::TALENT->value => 'wiki_talent_basics',
        ResourceType::SONG->value => 'wiki_song_basics',
    ];

    public function process(SearchMasterWikisInputPort $input, SearchMasterWikisOutputPort $output): void
    {
        $resourceType = $input->resourceType()->value;
        $basicTable = self::BASIC_TABLES[$resourceType];
        $keyword = $input->keyword();

        /** @var list<WikiModel> $wikis */
        $wikis = WikiModel::query()
            ->select('wikis.id', 'wikis.slug', 'wikis.resource_type', "{$basicTable}.name")
            ->join($basicTable, "{$basicTable}.wiki_id", '=', 'wikis.id')
            ->where('wikis.language', $input->language()->value)
            ->where('wikis.resource_type', $resourceType)
            ->where(function (Builder $query) use ($basicTable, $keyword): void {
                $query
                    ->where(fn (Builder $query) => $this->whereLike($query, "{$basicTable}.name", $keyword))
                    ->orWhere(fn (Builder $query) => $this->whereLike($query, "{$basicTable}.normalized_name", $keyword))
                    ->orWhere(fn (Builder $query) => $this->whereLike($query, 'wikis.slug', $keyword));
            })
            ->orderBy("{$basicTable}.name")
            ->orderBy('wikis.id')
            ->limit($input->limit())
            ->get()
            ->all();

        $output->output(array_map(
            static fn (WikiModel $wiki): WikiMasterSearchItemReadModel => new WikiMasterSearchItemReadModel(
                id: $wiki->id,
                name: (string) $wiki->getAttribute('name'),
                slug: $wiki->slug,
                resourceType: $wiki->resource_type,
            ),
            $wikis,
        ));
    }
}
