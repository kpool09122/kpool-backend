<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Query;

use Application\Models\Wiki\Wiki as WikiModel;
use Illuminate\Database\Query\JoinClause;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\GetWikiStatistics\GetWikiStatisticsInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiStatisticsReadModel;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupType;

readonly class GetWikiStatistics implements GetWikiStatisticsInterface
{
    public function process(): WikiStatisticsReadModel
    {
        $languages = array_fill_keys(array_column(Language::cases(), 'value'), 0);
        $resourceTypes = array_fill_keys([ResourceType::AGENCY->value, ResourceType::GROUP->value, ResourceType::TALENT->value, ResourceType::SONG->value], 0);
        $groupTypes = array_fill_keys([...array_column(GroupType::cases(), 'value'), 'unknown'], 0);
        $total = 0;

        // One aggregate query keeps all breakdowns on the same database snapshot.
        // Public pages live in wikis; drafts and pending reviews use other tables.
        $rows = WikiModel::query()
            ->leftJoin('wiki_group_basics', function (JoinClause $join): void {
                $join->on('wiki_group_basics.wiki_id', '=', 'wikis.id')
                    ->where('wikis.resource_type', ResourceType::GROUP->value);
            })
            ->whereIn('wikis.language', array_keys($languages))
            ->whereIn('wikis.resource_type', array_keys($resourceTypes))
            ->selectRaw('wikis.language, wikis.resource_type, wiki_group_basics.group_type, COUNT(*) as page_count')
            ->groupBy('wikis.language', 'wikis.resource_type', 'wiki_group_basics.group_type')
            ->get();

        foreach ($rows as $row) {
            $language = TypedValue::string($row->getAttribute('language'));
            $resourceType = TypedValue::string($row->getAttribute('resource_type'));
            $count = TypedValue::numericInt($row->getAttribute('page_count'));
            $total += $count;
            $languages[$language] += $count;
            $resourceTypes[$resourceType] += $count;
            if ($resourceType === ResourceType::GROUP->value) {
                $groupType = TypedValue::nullableString($row->getAttribute('group_type')) ?? 'unknown';
                $groupTypes[array_key_exists($groupType, $groupTypes) ? $groupType : 'unknown'] += $count;
            }
        }

        return new WikiStatisticsReadModel($total, $languages, $resourceTypes, $groupTypes);
    }
}
