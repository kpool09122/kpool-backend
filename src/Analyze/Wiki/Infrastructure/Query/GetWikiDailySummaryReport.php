<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Infrastructure\Query;

use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use Application\Models\Wiki\WikiHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInputPort;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInterface;
use Source\Analyze\Wiki\Application\UseCase\Query\WikiDailySummaryReportReadModel;

/**
 * @phpstan-type MetricAggregate array{value: ?string, denominator: ?int, quality: list<list<string>>, revision: ?string, updatedAt: ?string}
 * @phpstan-type SummaryAggregate SummaryModel&object{report_day: ?string}
 * @phpstan-type OperationAggregate WikiHistory&object{report_day: ?string, value: int, actor_count: int, missing_count: int, revision: ?string}
 */
readonly class GetWikiDailySummaryReport implements GetWikiDailySummaryReportInterface
{
    public function process(GetWikiDailySummaryReportInputPort $input): WikiDailySummaryReportReadModel
    {
        [$submissions, $publications] = $this->operations($input);
        [$views, $viewers, $engagementTime] = $this->summaries($input);

        return new WikiDailySummaryReportReadModel($views, $viewers, $engagementTime, $submissions, $publications);
    }

    /** @return array{MetricReportReadModel, MetricReportReadModel, MetricReportReadModel} */
    private function summaries(GetWikiDailySummaryReportInputPort $input): array
    {
        $metrics = [
            ['views', new MetricDefinition('ga4', Metric::VIEWS, 'wiki_language_country', '1')],
            ['viewers', new MetricDefinition('ga4', Metric::VIEWERS, 'wiki_language_country', '1')],
            ['engagement_seconds', new MetricDefinition('ga4', Metric::ENGAGEMENT_SECONDS, 'wiki_language_country', '1')],
        ];
        $source = SummaryModel::query()
            ->where('day', '>=', (string) $input->period()->start())
            ->where('day', '<', (string) $input->period()->endExclusive())
            ->select('*');
        foreach (['wiki_identifier' => $input->wikiIdentifier(), 'translation_set_identifier' => $input->translationSetIdentifier(), 'language' => $input->language(), 'country' => $input->country()] as $column => $filter) {
            if ($filter !== null) {
                $source->where($column, $filter);
            }
        }
        foreach ($metrics as [$column, $definition]) {
            $key = $definition->metric->value;
            $source->selectRaw("(measurements->'$key'->>'source' = ? AND measurements->'$key'->>'metric' = ? AND measurements->'$key'->>'grain' = ? AND measurements->'$key'->>'version' = ? AND $column IS NOT NULL) AS {$column}_valid", [$definition->source, $key, $definition->grain, $definition->version]);
        }
        $query = SummaryModel::query()->fromSub($source, 'summaries')->selectRaw('CAST(day AS text) AS report_day');
        $casts = [];
        foreach ($metrics as [$column, $definition]) {
            $key = $definition->metric->value;
            // Definitions are selected internally; each metric can be missing independently.
            $valid = $column . '_valid';
            $denominator = $definition->metric === Metric::ENGAGEMENT_SECONDS
                ? "CASE WHEN COUNT(*) FILTER (WHERE $valid) = COUNT(engagement_denominator) FILTER (WHERE $valid) THEN SUM(engagement_denominator) FILTER (WHERE $valid) ELSE NULL END"
                : 'NULL';
            $alias = $column . '_report';
            $query->selectRaw("JSONB_BUILD_OBJECT(
                'value', (SUM($column) FILTER (WHERE $valid))::text,
                'denominator', $denominator,
                'quality', COALESCE(JSONB_AGG(DISTINCT measurements->'$key'->'quality') FILTER (WHERE $valid), '[]'::jsonb),
                'revision', MD5(STRING_AGG(CONCAT(id, ':', revision), ',' ORDER BY id) FILTER (WHERE $valid)),
                'updatedAt', MAX(updated_at) FILTER (WHERE $valid)
            ) AS $alias");
            $casts[$alias] = 'array';
        }
        /** @var Collection<int, SummaryAggregate> $rows */
        $rows = $query->withCasts($casts)->groupByRaw('GROUPING SETS ((day), ())')->get();
        $totalRow = $rows->firstWhere('report_day', null);
        $daily = $rows->filter(static fn ($row): bool => $row->report_day !== null)->keyBy('report_day');
        $reports = [];
        foreach ($metrics as [$column, $definition]) {
            $alias = $column . '_report';
            /** @var MetricAggregate|null $totals */
            $totals = $totalRow?->getAttribute($alias);
            $days = [];
            foreach ($input->period()->days() as $day) {
                /** @var MetricAggregate|null $values */
                $values = $daily->get((string) $day)?->getAttribute($alias);
                $days[] = [
                    'day' => (string) $day,
                    'availability' => ($values['value'] ?? null) === null ? 'missing' : 'measured',
                    'value' => $values['value'] ?? null,
                    'denominator' => $values['denominator'] ?? null,
                    'quality' => array_values(array_unique(array_merge(...($values['quality'] ?? [])))),
                    'updatedAt' => $values['updatedAt'] ?? null,
                ];
            }
            $total = $totals['value'] ?? null;
            $reports[] = new MetricReportReadModel($definition, $totals['revision'] ?? null, $days, $total, $totals['denominator'] ?? null);
        }

        return [$reports[0], $reports[1], $reports[2]];
    }

    /** @return array{MetricReportReadModel, MetricReportReadModel} */
    private function operations(GetWikiDailySummaryReportInputPort $input): array
    {
        $histories = WikiHistory::query()->from('wiki_histories AS history')
            ->leftJoin('draft_wikis AS draft', 'draft.id', '=', 'history.draft_wiki_id')
            ->where('history.recorded_at', '>=', $input->period()->start()->instant())
            ->where('history.recorded_at', '<', $input->period()->endExclusive()->instant())
            ->where(static function (Builder $query): void {
                $query->where('history.action_type', 'publish')->orWhere(static fn (Builder $submission) => $submission->where('history.action_type', 'draft_status_change')->where('history.to_status', 'under_review'));
            })
            ->select('history.*')
            ->selectRaw("CAST(history.recorded_at AS DATE) AS day, COALESCE(history.visitor_country, '') AS country")
            ->selectRaw("COALESCE(history.wiki_id, draft.published_wiki_id, (SELECT publication.wiki_id FROM wiki_histories AS publication WHERE publication.draft_wiki_id = history.draft_wiki_id AND publication.action_type = 'publish' AND publication.wiki_id IS NOT NULL ORDER BY publication.recorded_at, publication.id LIMIT 1)) AS resolved_wiki_id");
        $operations = WikiHistory::query()->fromSub($histories, 'operations');
        if ($input->wikiIdentifier() !== null) {
            $operations->where(static function (Builder $query) use ($input): void {
                $query->where('resolved_wiki_id', $input->wikiIdentifier())->orWhere(static function (Builder $missing): void {
                    // Pending submissions belong to their translation set until publication.
                    $missing->whereNull('resolved_wiki_id')->where(static fn (Builder $target) => $target->whereNull('draft_wiki_id')->orWhere('action_type', 'publish'));
                });
            });
        }
        foreach (['translation_set_identifier' => $input->translationSetIdentifier(), 'language' => $input->language()] as $column => $filter) {
            if ($filter !== null) {
                // Keep missing attribution visible as incomplete instead of silently dropping it.
                $operations->where(static fn (Builder $query) => $query->where($column, $filter)->orWhereNull($column));
            }
        }
        if ($input->country() !== null) {
            $operations->where('country', $input->country());
        }
        $missingAttribution = "translation_set_identifier IS NULL OR language IS NULL OR (resolved_wiki_id IS NULL AND draft_wiki_id IS NULL) OR (action_type = 'publish' AND resolved_wiki_id IS NULL)";
        $aggregate = 'COUNT(*) AS value, COUNT(DISTINCT actor_id) AS actor_count, COUNT(*) FILTER (WHERE ' . $missingAttribution . ') AS missing_count';
        /** @var Collection<int, OperationAggregate> $rows */
        $rows = $operations->select('action_type')->selectRaw('CAST(day AS text) AS report_day')->selectRaw($aggregate)
            ->selectRaw("MD5(STRING_AGG(CONCAT_WS(':', id, day, action_type, COALESCE(resolved_wiki_id::text, ''), actor_id, COALESCE(translation_set_identifier::text, ''), COALESCE(language, ''), country), ',' ORDER BY id)) AS revision")
            ->groupByRaw('GROUPING SETS ((day, action_type), (action_type))')->get();
        $reports = [];
        foreach ([Metric::SUBMISSIONS, Metric::PUBLICATIONS] as $metric) {
            $definition = new MetricDefinition('wiki_history', $metric, 'wiki_language_country', '1');
            $action = $metric === Metric::SUBMISSIONS ? 'draft_status_change' : 'publish';
            $matching = $rows->where('action_type', $action);
            $totals = $matching->firstWhere('report_day', null);
            $daily = $matching->filter(static fn ($row): bool => $row->report_day !== null)->keyBy('report_day');
            $days = [];
            foreach ($input->period()->days() as $day) {
                $values = $daily->get((string) $day);
                $attributionMissing = ($values->missing_count ?? 0) > 0;
                $days[] = [
                    'day' => (string) $day,
                    'availability' => $attributionMissing ? 'incomplete' : 'measured',
                    'value' => $attributionMissing ? null : (string) ($values->value ?? 0),
                    'denominator' => null,
                    'quality' => $attributionMissing ? ['history_attribution_missing'] : [],
                    // Direct history reads have no batch import timestamp.
                    'updatedAt' => null,
                    'actorCount' => $attributionMissing ? null : (string) ($values->actor_count ?? 0),
                ];
            }
            $complete = ($totals->missing_count ?? 0) === 0;
            $reports[] = new MetricReportReadModel($definition, $totals->revision ?? md5(''), $days, $complete ? (string) ($totals->value ?? 0) : null, null, $complete ? (string) ($totals->actor_count ?? 0) : null);
        }

        return [$reports[0], $reports[1]];
    }
}
