<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Illuminate\Database\Eloquent\Builder;
use Source\Shared\Infrastructure\Trait\WhereLike;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisInputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\SearchTranslationSetMasterWikis\SearchTranslationSetMasterWikisOutputPort;
use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchItemReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TranslationSetMasterSearchWikiReadModel;

readonly class SearchTranslationSetMasterWikis implements SearchTranslationSetMasterWikisInterface
{
    use WhereLike;

    /** @var array<string, string> */
    private const BASIC_TABLES = [
        ResourceType::AGENCY->value => 'wiki_agency_basics',
        ResourceType::GROUP->value => 'wiki_group_basics',
        ResourceType::TALENT->value => 'wiki_talent_basics',
        ResourceType::SONG->value => 'wiki_song_basics',
    ];

    public function process(SearchTranslationSetMasterWikisInputPort $input, SearchTranslationSetMasterWikisOutputPort $output): void
    {
        $resourceType = $input->resourceType()->value;
        $basicTable = self::BASIC_TABLES[$resourceType];
        $keyword = $input->keyword();

        /** @var list<string> $translationSetIdentifiers */
        $translationSetIdentifiers = WikiModel::query()
            ->select('wikis.translation_set_identifier')
            ->join($basicTable, "{$basicTable}.wiki_id", '=', 'wikis.id')
            ->where('wikis.resource_type', $resourceType)
            ->where(function (Builder $query) use ($basicTable, $keyword): void {
                $query
                    ->where(fn (Builder $query) => $this->whereLike($query, "{$basicTable}.name", $keyword))
                    ->orWhere(fn (Builder $query) => $this->whereLike($query, "{$basicTable}.normalized_name", $keyword))
                    ->orWhere(fn (Builder $query) => $this->whereLike($query, 'wikis.slug', $keyword));
            })
            ->groupBy('wikis.translation_set_identifier')
            ->orderByRaw("MIN({$basicTable}.name)")
            ->orderBy('wikis.translation_set_identifier')
            ->limit($input->limit())
            ->pluck('wikis.translation_set_identifier')
            ->all();

        if ($translationSetIdentifiers === []) {
            $output->output([]);

            return;
        }

        /** @var list<WikiModel> $wikis */
        $wikis = WikiModel::query()
            ->select('wikis.id', 'wikis.translation_set_identifier', 'wikis.slug', 'wikis.language', 'wikis.resource_type', "{$basicTable}.name")
            ->join($basicTable, "{$basicTable}.wiki_id", '=', 'wikis.id')
            ->where('wikis.resource_type', $resourceType)
            ->whereIn('wikis.translation_set_identifier', $translationSetIdentifiers)
            ->orderBy('wikis.translation_set_identifier')
            ->orderBy('wikis.language')
            ->orderBy('wikis.id')
            ->get()
            ->all();

        $grouped = [];
        foreach ($wikis as $wiki) {
            $translationSetIdentifier = $wiki->translation_set_identifier;
            $grouped[$translationSetIdentifier][] = new TranslationSetMasterSearchWikiReadModel(
                wikiIdentifier: $wiki->id,
                language: $wiki->language,
                name: (string) $wiki->getAttribute('name'),
                slug: $wiki->slug,
            );
        }

        $translationSetMasters = [];
        foreach ($translationSetIdentifiers as $translationSetIdentifier) {
            $translationSetMasters[] = new TranslationSetMasterSearchItemReadModel(
                translationSetIdentifier: $translationSetIdentifier,
                resourceType: $resourceType,
                wikis: $grouped[$translationSetIdentifier] ?? [],
            );
        }

        $output->output($translationSetMasters);
    }
}
