<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Query;

use Application\Models\Analyze\RegionalDailySummary;
use Illuminate\Database\Eloquent\Builder;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInterface;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInputPort;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Analyze\Principal\Domain\ValueObject\Action;
use Source\Analyze\Principal\Domain\ValueObject\Resource;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Application\UseCase\Query\RegionalMetricReportReadModel;
use Source\Analyze\Shared\Application\UseCase\Query\RegionalReportReadModel;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Shared\Domain\ValueObject\AdministrativeAreaCode;

/**
 * @phpstan-type Aggregate RegionalDailySummary&object{report_day: ?string, total: ?string, samples: ?int, qualities: list<list<string>>, report_revision: ?string, latest: ?string, missing_count: int}
 */
readonly class GetAppRegionalDailySummaryReport implements GetAppRegionalDailySummaryReportInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private GetAppDailySummaryReportInterface $getAppDailySummaryReport,
    ) {
    }

    /** @throws UnauthorizedException */
    public function process(GetAppRegionalDailySummaryReportInputPort $input): RegionalReportReadModel
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANALYSIS_VIEW, new Resource())) {
            throw new UnauthorizedException();
        }
        $country = $this->getAppDailySummaryReport->process($input);
        $reports = $this->regionalReports($input, ['applicationUsers' => ['application_users', $country->applicationUsers()], 'registeredUsers' => ['registered_users', $country->registeredUsers()]]);

        return new RegionalReportReadModel($reports);
    }

    /**
     * @param array<string, array{string, MetricReportReadModel}> $countryReports
     *
     * @return array<string, RegionalMetricReportReadModel>
     */
    private function regionalReports(GetAppRegionalDailySummaryReportInputPort $input, array $countryReports): array
    {
        $period = $input->period();
        $selection = $input->selection();
        $query = RegionalDailySummary::query()->where('context', 'app')->where('country', $selection->country->value)
            ->where('day', '>=', (string) $period->start())->where('day', '<', (string) $period->endExclusive())
            ->where(function (Builder $query) use ($countryReports): void {
                foreach ($countryReports as [$column, $countryReport]) {
                    $definition = $countryReport->definition();
                    $query->orWhere(function (Builder $query) use ($column, $definition): void {
                        $query->where('metric', $column)->where('source', $definition->source)->where('definition_version', $definition->version);
                    });
                }
            });
        if ($selection->region !== null) {
            $query->where('region', $selection->region);
        }
        /** @var iterable<Aggregate> $rows */
        $rows = $query->select('metric', 'region')->selectRaw("day::text AS report_day, SUM(value)::text AS total,
            CASE WHEN COUNT(denominator) = COUNT(*) THEN SUM(denominator) ELSE NULL END AS samples,
            JSONB_AGG(quality) AS qualities, COUNT(*) FILTER (WHERE value IS NULL) AS missing_count,
            MD5(STRING_AGG(revision::text, ',' ORDER BY context, day, country, region, wiki_identifier, language, metric)) AS report_revision, MAX(updated_at)::text AS latest")
            ->withCasts(['qualities' => 'array', 'samples' => 'integer', 'missing_count' => 'integer'])
            ->groupByRaw('GROUPING SETS ((metric, day, region), (metric, region))')->orderBy('metric')->orderBy('region')->get();
        $byMetric = [];
        foreach ($rows as $row) {
            $byMetric[$row->metric][] = $row;
        }
        $reports = [];
        foreach ($countryReports as $key => [$column, $countryReport]) {
            $reports[$key] = $this->regionalReport($byMetric[$column] ?? [], $countryReport, $period, $selection);
        }

        return $reports;
    }

    /** @param list<Aggregate> $rows */
    private function regionalReport(array $rows, MetricReportReadModel $countryReport, ReportingPeriod $period, RegionalSelection $selection): RegionalMetricReportReadModel
    {
        $definition = $countryReport->definition();
        $totals = [];
        $daily = [];
        foreach ($rows as $row) {
            if ($row->report_day === null) {
                $totals[$row->region] = $row;
            } else {
                $daily[$row->region][$row->report_day] = $row;
            }
        }
        $regions = [];
        foreach ($totals as $code => $row) {
            $last = $daily[$code][(string) $period->endExclusive()->instant()->modify('-1 day')->format('Y-m-d')] ?? null;
            $regions[] = ['region' => $code === '' ? null : $code, 'mapSupported' => AdministrativeAreaCode::tryFrom($code) !== null,
                'value' => $definition->metric->isSnapshot() ? $last?->total : $row->total, 'denominator' => $definition->metric->isSnapshot() ? null : $row->samples,
                'quality' => array_values(array_unique(array_merge(...$row->qualities)))];
        }
        if ($selection->region === null) {
            $revision = hash('sha256', json_encode(['country' => $countryReport->revision(), 'regions' => array_map(static fn ($row): ?string => $row->report_revision, $totals)], JSON_THROW_ON_ERROR));
            $combined = new MetricReportReadModel($definition, $revision, $countryReport->days(), $countryReport->total(), $countryReport->denominator(), $countryReport->actorCount());

            return new RegionalMetricReportReadModel($combined, $regions);
        }
        $selected = $totals[$selection->region] ?? null;
        $days = [];
        foreach ($period->days() as $day) {
            $row = $daily[$selection->region][(string) $day] ?? null;
            $days[] = ['day' => (string) $day, 'availability' => $row === null ? 'not_collected' : ($row->total === null ? 'missing' : ($row->missing_count > 0 ? 'incomplete' : 'measured')),
                'value' => $row?->total, 'denominator' => $row?->samples, 'quality' => $row !== null ? array_values(array_unique(array_merge(...$row->qualities))) : [], 'updatedAt' => $row?->latest];
        }
        $lastDay = $days === [] ? ['value' => null] : $days[array_key_last($days)];
        $total = $definition->metric->isSnapshot() ? $lastDay['value'] : $selected?->total;
        $regionalDefinition = new MetricDefinition($definition->source, $definition->metric, $definition->grain . '_region', $definition->version);

        return new RegionalMetricReportReadModel(new MetricReportReadModel($regionalDefinition, $selected?->report_revision, $days, $total, $selected?->samples), $regions);
    }
}
