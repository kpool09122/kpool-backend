<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Infrastructure\Query;

use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInput;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Query\GetAppDailySummaryReport;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\TestCase;

#[Group('useDb')]
class GetAppDailySummaryReportTest extends TestCase
{
    public function testDailyUsersAreAddedButRegisteredUsersUseOnlyTheFinalClosingBalance(): void
    {
        $first = new UtcDate('2020-01-01');
        $this->save($first, ['application_users' => '5', 'registered_users' => '100']);
        $this->save($first->next(), ['application_users' => '7', 'registered_users' => '110']);
        $query = new GetAppDailySummaryReport();
        $period = new ReportingPeriod($first, $first->next(), AnalyzeAppMetrics::now());
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $report = $query->process(new GetAppDailySummaryReportInput($period));
            self::assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        self::assertSame('12.000000000', $report->applicationUsers()->total());
        self::assertSame('110.000000000', $report->registeredUsers()->total());
        self::assertNull($query->process(new GetAppDailySummaryReportInput(new ReportingPeriod($first, $first->next()->next(), AnalyzeAppMetrics::now())))->registeredUsers()->total());
    }

    public function testUnknownAndMeasuredZeroRemainDistinct(): void
    {
        $first = new UtcDate('2020-01-01');
        $definition = AnalyzeAppMetrics::definitions()[0];
        $this->save($first, ['application_users' => '0']);
        $report = (new GetAppDailySummaryReport())->process(new GetAppDailySummaryReportInput(new ReportingPeriod($first, $first->next(), AnalyzeAppMetrics::now())))->applicationUsers();
        self::assertSame(['0.000000000', null], array_column($report->days(), 'value'));
        self::assertSame(['measured', 'missing'], array_column($report->days(), 'availability'));
    }

    public function testSourceAndDefinitionVersionsNeverMix(): void
    {
        $day = new UtcDate('2020-01-01');
        $factory = new AppDailySummaryFactory(new UuidGenerator());
        $appDailySummaryRepository = new AppDailySummaryRepository();
        foreach ([['ga4', '1', '2'], ['ga4', '2', '3'], ['other', '1', '7']] as $index => [$source, $version, $count]) {
            $targetDay = new UtcDate('2020-01-0' . ($index + 1));
            $definition = new MetricDefinition($source, Metric::APPLICATION_USERS, 'application_day', $version);
            $values = AnalyzeAppMetrics::analysisValues($targetDay);
            $values[0] = new ApplicationUserCount($count, new MetricProvenance($definition, $targetDay, AnalyzeAppMetrics::now()));
            $appDailySummaryRepository->save($factory->create($values[0], $values[1]));
        }
        $report = (new GetAppDailySummaryReport())->process(new GetAppDailySummaryReportInput(new ReportingPeriod($day, new UtcDate('2020-01-03'), AnalyzeAppMetrics::now())))->applicationUsers();
        self::assertSame('2.000000000', $report->total());
    }

    public function testReportRevisionTracksItsOwnSummaryRows(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $input = new GetAppDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeAppMetrics::now()));
        $query = new GetAppDailySummaryReport();
        $revision = $query->process($input)->applicationUsers()->revision();
        self::assertNotNull($revision);
        self::assertSame($revision, $query->process($input)->applicationUsers()->revision());
        $this->save($day->next());
        self::assertSame($revision, $query->process($input)->applicationUsers()->revision());
        $repository = new AppDailySummaryRepository();
        $summary = $repository->findByDay($day);
        self::assertNotNull($summary);
        $values = AnalyzeAppMetrics::analysisValues($day, ['application_users' => '9']);
        $summary->replaceAnalysis(...$values);
        $repository->save($summary);
        self::assertNotSame($revision, $query->process($input)->applicationUsers()->revision());
        self::assertSame('9.000000000', $query->process($input)->applicationUsers()->total());
    }

    #[DataProvider('differentDefinitions')]
    public function testDifferentStoredDefinitionValuesAreMissing(MetricDefinition $definition): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $row = SummaryModel::query()->firstOrFail();
        $measurements = $row->measurements;
        $measurements[$definition->metric->value]['source'] = $definition->source;
        $measurements[$definition->metric->value]['version'] = $definition->version;
        $row->forceFill(['measurements' => $measurements])->save();
        $report = (new GetAppDailySummaryReport())->process(new GetAppDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeAppMetrics::now())))->applicationUsers();
        self::assertNull($report->total());
        self::assertNull($report->revision());
        self::assertSame('missing', $report->days()[0]['availability']);
    }

    /** @return array<string, array{MetricDefinition}> */
    public static function differentDefinitions(): array
    {
        return [
            'source' => [new MetricDefinition('other', Metric::APPLICATION_USERS, 'application_day', '1')],
            'version' => [new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', '2')],
        ];
    }

    /** @param array<string, string> $overrides */
    private function save(UtcDate $day, array $overrides = []): void
    {
        $values = AnalyzeAppMetrics::analysisValues($day, $overrides);
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create($values[0], $values[1]);
        (new AppDailySummaryRepository())->save($summary);
    }

    public function testReportReadsAnalyticalSummaryWithoutAnImportCheckpoint(): void
    {
        $definition = AnalyzeAppMetrics::definition();
        $day = new UtcDate('2020-01-01');
        $now = AnalyzeAppMetrics::now();
        $values = AnalyzeAppMetrics::analysisValues($day);
        $values[0] = new ApplicationUserCount('5', new MetricProvenance($definition, $day, $now, ['sampled']));
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create($values[0], $values[1]);
        (new AppDailySummaryRepository())->save($summary);
        $report = (new GetAppDailySummaryReport())->process(new GetAppDailySummaryReportInput(new ReportingPeriod($day, $day, $now)))->applicationUsers();
        self::assertSame('5.000000000', $report->total());
        self::assertSame('5.000000000', $report->days()[0]['value']);
        self::assertSame('measured', $report->days()[0]['availability']);
        self::assertSame(['sampled'], $report->days()[0]['quality']);
    }

    public function testReplacingOtherMetricMetadataRetainsOneDailySummary(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::analysisValues($day);
        $definition = $values[0]->provenance()->definition();
        $factory = new AppDailySummaryFactory(new UuidGenerator());
        $appDailySummaryRepository = new AppDailySummaryRepository();
        $appDailySummaryRepository->save($factory->create($values[0], $values[1]));
        $other = AnalyzeAppMetrics::definition(Metric::REGISTERED_USERS, 'alternate');
        $values[0] = new ApplicationUserCount('9', new MetricProvenance($definition, $day, AnalyzeAppMetrics::now()));
        $values[1] = new RegisteredUserSnapshot('11', $day->next()->instant(), new MetricProvenance($other, $day, AnalyzeAppMetrics::now()));
        $summary = $appDailySummaryRepository->findByDay($day);
        self::assertNotNull($summary);
        $summary->replaceAnalysis($values[0], $values[1]);
        $appDailySummaryRepository->save($summary);
        $input = new GetAppDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeAppMetrics::now()));
        self::assertSame('9.000000000', (new GetAppDailySummaryReport())->process($input)->applicationUsers()->total());
    }

    public function testReadModelDoesNotRequireEntityProvenanceReconstruction(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $row = SummaryModel::query()->firstOrFail();
        $measurements = $row->measurements;
        $measurements[Metric::APPLICATION_USERS->value]['updated_at'] = 'not-a-domain-timestamp';
        $row->forceFill(['measurements' => $measurements])->save();
        $report = (new GetAppDailySummaryReport())->process(new GetAppDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeAppMetrics::now())))->applicationUsers();
        self::assertSame('5.000000000', $report->total());
        self::assertSame('measured', $report->days()[0]['availability']);
        self::assertNotNull($report->days()[0]['updatedAt']);
    }
}
