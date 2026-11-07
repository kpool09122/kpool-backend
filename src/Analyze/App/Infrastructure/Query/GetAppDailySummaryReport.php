<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Query;

use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use Illuminate\Support\Collection;
use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInputPort;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInterface;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;

/**
 * @phpstan-type MetricAggregate array{value: ?string, denominator: ?int, quality: list<list<string>>, revision: ?string, updatedAt: ?string}
 * @phpstan-type SummaryAggregate SummaryModel&object{report_day: ?string}
 */
readonly class GetAppDailySummaryReport implements GetAppDailySummaryReportInterface
{
    public function process(GetAppDailySummaryReportInputPort $input): AppDailySummaryReportReadModel
    {
        [$applicationUsers, $registeredUsers] = $this->summaries($input);

        return new AppDailySummaryReportReadModel($applicationUsers, $registeredUsers);
    }

    /** @return array{MetricReportReadModel, MetricReportReadModel} */
    private function summaries(GetAppDailySummaryReportInputPort $input): array
    {
        $metrics = [
            ['application_users', new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', '1')],
            ['registered_users', new MetricDefinition('identity_history', Metric::REGISTERED_USERS, 'application_day', '1')],
        ];
        $source = SummaryModel::query()
            ->where('day', '>=', (string) $input->period()->start())
            ->where('day', '<', (string) $input->period()->endExclusive())
            ->select('*');
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
            $alias = $column . '_report';
            $query->selectRaw("JSONB_BUILD_OBJECT(
                'value', (SUM($column) FILTER (WHERE $valid))::text,
                'denominator', NULL,
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
            $last = $days[array_key_last($days) ?? 0] ?? null;
            $total = $definition->metric->isSnapshot() ? ($last['value'] ?? null) : ($totals['value'] ?? null);
            $reports[] = new MetricReportReadModel($definition, $totals['revision'] ?? null, $days, $total, $totals['denominator'] ?? null);
        }

        return [$reports[0], $reports[1]];
    }
}
