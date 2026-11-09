<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Query;

use Application\Models\Analyze\App\AppCountryDailySummary as CountryModel;
use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Source\Analyze\App\Application\UseCase\Query\AppDailySummaryReportReadModel;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInputPort;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Analyze\Principal\Domain\ValueObject\Action;
use Source\Analyze\Principal\Domain\ValueObject\Resource;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;

/**
 * @phpstan-type MetricAggregate array{invalid: int, value: ?string, denominator: ?int, quality: list<list<string>>, revision: ?string, updatedAt: ?string}
 * @phpstan-type SummaryAggregate SummaryModel&object{report_day: ?string, report_country: string}
 */
readonly class GetAppDailySummaryReport implements GetAppDailySummaryReportInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /** @throws UnauthorizedException */
    public function process(GetAppDailySummaryReportInputPort $input): AppDailySummaryReportReadModel
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANALYSIS_VIEW, new Resource())) {
            throw new UnauthorizedException();
        }

        [$applicationUsers, $registeredUsers] = $this->summaries($input);

        return new AppDailySummaryReportReadModel($applicationUsers, $registeredUsers);
    }

    /** @return array{MetricReportReadModel, MetricReportReadModel} */
    private function summaries(GetAppDailySummaryReportInputPort $input): array
    {
        /** @var list<array{literal-string, MetricDefinition}> $metrics */
        $metrics = [
            ['application_users', new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', '1')],
            ['registered_users', new MetricDefinition('identity_history', Metric::REGISTERED_USERS, 'application_day', '1')],
        ];
        $source = SummaryModel::query()
            ->where('day', '>=', (string) $input->period()->start())
            ->where('day', '<', (string) $input->period()->endExclusive())
            ->select('*')
            ->selectSub(CountryModel::query()->selectRaw('COALESCE(SUM(registered_users), 0)')->whereColumn('day', 'analyze_app_daily_summaries.day'), 'registered_users');
        $source = SummaryModel::query()->fromSub($source, 'daily_summaries')->select('*');
        foreach ($metrics as [$column, $definition]) {
            $key = $definition->metric->value;
            $source->selectRaw("(measurements->'$key'->>'source' = ? AND measurements->'$key'->>'metric' = ? AND measurements->'$key'->>'grain' = ? AND measurements->'$key'->>'version' = ? AND $column IS NOT NULL) AS {$column}_valid", [$definition->source, $key, $definition->grain, $definition->version]);
        }
        $base = $source;
        $fields = 'id, day, revision, measurements, updated_at';
        if ($input->country() === null) {
            $source = SummaryModel::query()->fromSub(clone $base, 'valid_summaries')->selectRaw("$fields, application_users, registered_users, application_users_valid, registered_users_valid, 'all'::text AS report_country");
            $countries = SummaryModel::query()->fromSub(clone $base, 'valid_summaries')
                ->join('analyze_app_country_daily_summaries as country_data', 'country_data.day', '=', 'valid_summaries.day')
                ->selectRaw("valid_summaries.id, valid_summaries.day, revision, measurements, updated_at, country_data.application_users, country_data.registered_users, (application_users_valid AND country_data.application_users IS NOT NULL) AS application_users_valid, (registered_users_valid AND country_data.registered_users IS NOT NULL) AS registered_users_valid, country_data.country AS report_country");
            $source->unionAll($countries);
        } else {
            $source = SummaryModel::query()->fromSub($base, 'valid_summaries')
                ->leftJoin('analyze_app_country_daily_summaries as country_data', static function (JoinClause $join) use ($input): void {
                    $join->on('country_data.day', '=', 'valid_summaries.day')->where('country_data.country', $input->country());
                })
                ->selectRaw("valid_summaries.id, valid_summaries.day, revision, measurements, updated_at, ?::text AS report_country", [$input->country()]);
            foreach ($metrics as [$column]) {
                $source->selectRaw("CASE WHEN country_data.id IS NULL THEN 0::numeric(30,9) ELSE country_data.$column END AS $column, (valid_summaries.{$column}_valid AND (country_data.id IS NULL OR country_data.$column IS NOT NULL)) AS {$column}_valid");
            }
        }
        $query = SummaryModel::query()->fromSub($source, 'summaries')->selectRaw('CAST(day AS text) AS report_day, report_country');
        $casts = [];
        foreach ($metrics as [$column, $definition]) {
            $key = $definition->metric->value;
            // Definitions are selected internally; each metric can be missing independently.
            $valid = $column . '_valid';
            $alias = $column . '_report';
            $query->selectRaw("JSONB_BUILD_OBJECT(
                'value', (SUM($column) FILTER (WHERE $valid))::text,
                'invalid', COUNT(*) FILTER (WHERE NOT COALESCE($valid, FALSE)),
                'denominator', NULL,
                'quality', COALESCE(JSONB_AGG(DISTINCT measurements->'$key'->'quality') FILTER (WHERE $valid), '[]'::jsonb),
                'revision', MD5(STRING_AGG(CONCAT(id, ':', revision), ',' ORDER BY id) FILTER (WHERE $valid)),
                'updatedAt', MAX(updated_at) FILTER (WHERE $valid)
            ) AS $alias");
            $casts[$alias] = 'array';
        }
        /** @var Collection<int, SummaryAggregate> $rows */
        $rows = $query->withCasts($casts)->groupByRaw('GROUPING SETS ((day, report_country), (report_country))')->orderBy('report_country')->get();
        $selection = $rows->where('report_country', $input->country() ?? 'all');
        $totalRow = $selection->firstWhere('report_day', null);
        $daily = $selection->filter(static fn ($row): bool => $row->report_day !== null)->keyBy('report_day');
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
                    'availability' => ($values['value'] ?? null) === null ? ($values === null ? 'not_collected' : 'missing') : (($values['invalid'] ?? 0) > 0 ? 'incomplete' : 'measured'),
                    'value' => $values['value'] ?? null,
                    'denominator' => $values['denominator'] ?? null,
                    'quality' => array_values(array_unique(array_merge(array_merge(...($values['quality'] ?? [])), ($values['invalid'] ?? 0) > 0 ? ['metric_definition_missing'] : []))),
                    'updatedAt' => $values['updatedAt'] ?? null,
                ];
            }
            $last = $days[array_key_last($days) ?? 0] ?? null;
            $total = $definition->metric->isSnapshot() ? ($last['value'] ?? null) : ($totals['value'] ?? null);
            $countries = [];
            foreach ($rows->where('report_day', null) as $row) {
                if ($row->report_country === 'all') {
                    continue;
                }
                /** @var MetricAggregate $values */
                $values = $row->getAttribute($alias);
                $countryTotal = $values['value'];
                if ($definition->metric->isSnapshot()) {
                    $finalDay = $last['day'] ?? '';
                    $closing = $rows->where('report_country', $row->report_country)->firstWhere('report_day', $finalDay);
                    /** @var MetricAggregate|null $closingValues */
                    $closingValues = $closing?->getAttribute($alias);
                    $countryTotal = $closingValues['value'] ?? null;
                    if ($closing === null && ($last['value'] ?? null) !== null) {
                        $countryTotal = '0.000000000';
                    }
                }
                $countries[] = ['country' => $row->report_country === '' ? null : $row->report_country, 'value' => $countryTotal, 'denominator' => null,
                    'quality' => array_values(array_unique(array_merge(array_merge(...$values['quality']), $values['invalid'] > 0 ? ['metric_definition_missing'] : [])))];
            }
            $reports[] = new MetricReportReadModel($definition, $totals['revision'] ?? null, $days, $total, $totals['denominator'] ?? null, countries: $countries);
        }

        return [$reports[0], $reports[1]];
    }
}
