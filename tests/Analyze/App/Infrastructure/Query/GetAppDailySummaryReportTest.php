<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Infrastructure\Query;

use Application\Models\Analyze\App\AppCountryDailySummary as CountryModel;
use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use Illuminate\Support\Facades\DB;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInput;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Query\GetAppDailySummaryReport;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Symfony\Component\Uid\Uuid;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\TestCase;

#[Group('useDb')]
class GetAppDailySummaryReportTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized (setUp で初期化) */
    private PrincipalIdentifier $principalIdentifier;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->principalIdentifier = CreateAnalyzePrincipal::create()->principalIdentifier();
    }

    public function testCountryTrendsAndFinalSnapshotsRemainIndependentFromGlobalUsers(): void
    {
        $day = new UtcDate('2020-01-01');
        foreach ([$day, $day->next()] as $index => $date) {
            $this->save($date, ['application_users' => $index === 0 ? '5' : '7', 'registered_users' => $index === 0 ? '100' : '110']);
            CountryModel::query()->where('day', (string) $date)->delete();
            foreach ([
                'JP' => ['application_users' => $index === 0 ? '2' : '6', 'registered_users' => $index === 0 ? '60' : '70'],
                'US' => ['application_users' => $index === 0 ? '4' : '5', 'registered_users' => '40'],
                '' => ['application_users' => '0', 'registered_users' => '0'],
            ] as $country => $values) {
                CountryModel::query()->create(['id' => (string) Uuid::v7(), 'day' => (string) $date, 'country' => $country, ...$values]);
            }
        }
        $period = new ReportingPeriod($day, $day->next(), AnalyzeAppMetrics::now());
        $query = $this->app()->make(GetAppDailySummaryReport::class);
        $global = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period));
        self::assertSame('12.000000000', $global->applicationUsers()->total());
        self::assertSame('110.000000000', $global->registeredUsers()->total());
        self::assertSame(['0.000000000', '8.000000000', '9.000000000'], array_column($global->applicationUsers()->countries(), 'value'));
        self::assertSame(['0.000000000', '70.000000000', '40.000000000'], array_column($global->registeredUsers()->countries(), 'value'));
        $japan = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period, 'JP'));
        self::assertSame('8.000000000', $japan->applicationUsers()->total());
        self::assertSame(['2.000000000', '6.000000000'], array_column($japan->applicationUsers()->days(), 'value'));
        self::assertSame('70.000000000', $japan->registeredUsers()->total());
        self::assertSame(['JP'], array_column($japan->applicationUsers()->countries(), 'country'));
        $unknown = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period, ''));
        self::assertSame('0.000000000', $unknown->registeredUsers()->total());
        $absent = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period, 'DE'));
        self::assertSame(['measured', 'measured'], array_column($absent->applicationUsers()->days(), 'availability'));
        self::assertSame('0.000000000', $absent->registeredUsers()->total());
    }

    public function testPreviousPeriodComparesFilteredTotalsAndClosingBalances(): void
    {
        foreach (['2019-12-30', '2019-12-31', '2020-01-01', '2020-01-02'] as $index => $date) {
            $day = new UtcDate($date);
            $this->save($day, ['application_users' => $index < 2 ? '10' : '15', 'registered_users' => $index < 2 ? '100' : '120']);
            CountryModel::query()->where('day', $date)->delete();
            CountryModel::query()->create(['id' => (string) Uuid::v7(), 'day' => $date, 'country' => 'JP', 'application_users' => $index < 2 ? '2' : '4', 'registered_users' => $index < 2 ? '50' : '75']);
        }
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-02'), AnalyzeAppMetrics::now());
        $query = $this->app()->make(GetAppDailySummaryReport::class);
        $global = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period))->toArray();
        self::assertNotNull($global['applicationUsers']['comparison']);
        self::assertSame('50.0', $global['applicationUsers']['comparison']['percentageChange']);
        self::assertNotNull($global['registeredUsers']['comparison']);
        self::assertSame('50.0', $global['registeredUsers']['comparison']['percentageChange']);
        $japan = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period, 'JP'))->toArray();
        self::assertNotNull($japan['applicationUsers']['comparison']);
        self::assertSame('100.0', $japan['applicationUsers']['comparison']['percentageChange']);
        self::assertSame('2019-12-30', $japan['applicationUsers']['comparison']['from']);
        self::assertSame('2019-12-31', $japan['applicationUsers']['comparison']['to']);
        SummaryModel::query()->where('day', '2019-12-30')->delete();
        $missing = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period, 'JP'))->toArray();
        self::assertNotNull($missing['applicationUsers']['comparison']);
        self::assertSame('300.0', $missing['applicationUsers']['comparison']['percentageChange']);
        self::assertNull($missing['applicationUsers']['comparison']['reason']);
        self::assertNotNull($missing['registeredUsers']['comparison']);
        self::assertSame('50.0', $missing['registeredUsers']['comparison']['percentageChange']);
    }

    public function testAbsentCountryInACollectedDayIsMeasuredZero(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $query = $this->app()->make(GetAppDailySummaryReport::class);
        $period = new ReportingPeriod($day, $day, AnalyzeAppMetrics::now());
        $report = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period, 'JP'));
        self::assertSame('0.000000000', $report->applicationUsers()->total());
        self::assertSame('0.000000000', $report->registeredUsers()->total());
        self::assertSame('measured', $report->applicationUsers()->days()[0]['availability']);
        $missing = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day->next(), $day->next(), AnalyzeAppMetrics::now()), 'JP'));
        self::assertNull($missing->applicationUsers()->total());
        self::assertSame('not_collected', $missing->applicationUsers()->days()[0]['availability']);
    }

    public function testDailyUsersAreAddedButRegisteredUsersUseOnlyTheFinalClosingBalance(): void
    {
        $first = new UtcDate('2020-01-01');
        $this->save($first, ['application_users' => '5', 'registered_users' => '100']);
        $this->save($first->next(), ['application_users' => '7', 'registered_users' => '110']);
        $query = $this->app()->make(GetAppDailySummaryReport::class);
        $period = new ReportingPeriod($first, $first->next(), AnalyzeAppMetrics::now());
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $report = $query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, $period));
            self::assertCount(10, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        self::assertSame('12.000000000', $report->applicationUsers()->total());
        self::assertSame('110.000000000', $report->registeredUsers()->total());
        self::assertNull($query->process(new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($first, $first->next()->next(), AnalyzeAppMetrics::now())))->registeredUsers()->total());
    }

    public function testUnknownAndMeasuredZeroRemainDistinct(): void
    {
        $first = new UtcDate('2020-01-01');
        $definition = AnalyzeAppMetrics::definitions()[0];
        $this->save($first, ['application_users' => '0']);
        $report = ($this->app()->make(GetAppDailySummaryReport::class))->process(new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($first, $first->next(), AnalyzeAppMetrics::now())))->applicationUsers();
        self::assertSame(['0.000000000', null], array_column($report->days(), 'value'));
        self::assertSame(['measured', 'not_collected'], array_column($report->days(), 'availability'));
    }

    public function testSourceAndDefinitionVersionsNeverMix(): void
    {
        $day = new UtcDate('2020-01-01');
        $factory = new AppDailySummaryFactory(new UuidGenerator());
        $appDailySummaryRepository = new AppDailySummaryRepository();
        foreach ([['ga4', '1', '2'], ['ga4', '2', '3'], ['other', '1', '7']] as $index => [$source, $version, $count]) {
            $targetDay = new UtcDate('2020-01-0' . ($index + 1));
            $definition = new MetricDefinition($source, Metric::APPLICATION_USERS, 'application_day', $version);
            $values = AnalyzeAppMetrics::summaryValues($targetDay);
            $values[0] = new ApplicationUserCount($count, new MetricProvenance($definition, $targetDay, AnalyzeAppMetrics::now()));
            $appDailySummaryRepository->save($factory->create($values[0], $values[1], $values[2]));
        }
        $report = ($this->app()->make(GetAppDailySummaryReport::class))->process(new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, new UtcDate('2020-01-03'), AnalyzeAppMetrics::now())))->applicationUsers();
        self::assertSame('2.000000000', $report->total());
    }

    public function testUpdatingRegisteredUsersIsReflectedInTheNextReport(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day, ['application_users' => '5', 'registered_users' => '10']);
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create(...$values);
        $repository = new AppDailySummaryRepository();
        $repository->save($summary);
        $input = new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day, AnalyzeAppMetrics::now()));
        $query = $this->app()->make(GetAppDailySummaryReport::class);
        self::assertSame('10.000000000', $query->process($input)->registeredUsers()->total());
        $summary->replaceAnalysis(...AnalyzeAppMetrics::summaryValues($day, ['application_users' => '5', 'registered_users' => '22']), regions: $summary->regions());
        $repository->save($summary);
        self::assertSame('22.000000000', $query->process($input)->registeredUsers()->total());
        self::assertSame('5.000000000', $query->process($input)->applicationUsers()->total());
    }

    public function testReportRevisionTracksItsOwnSummaryRows(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $input = new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day, AnalyzeAppMetrics::now()));
        $query = $this->app()->make(GetAppDailySummaryReport::class);
        $revision = $query->process($input)->applicationUsers()->revision();
        self::assertNotNull($revision);
        self::assertSame($revision, $query->process($input)->applicationUsers()->revision());
        $this->save($day->next());
        self::assertSame($revision, $query->process($input)->applicationUsers()->revision());
        $repository = new AppDailySummaryRepository();
        $summary = $repository->findByDay($day);
        self::assertNotNull($summary);
        $values = AnalyzeAppMetrics::summaryValues($day, ['application_users' => '9']);
        $summary->replaceAnalysis(...$values, regions: $summary->regions());
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
        $report = ($this->app()->make(GetAppDailySummaryReport::class))->process(new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day, AnalyzeAppMetrics::now())))->applicationUsers();
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
        $values = AnalyzeAppMetrics::summaryValues($day, $overrides);
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create($values[0], $values[1], $values[2]);
        (new AppDailySummaryRepository())->save($summary);
    }

    public function testReportReadsAnalyticalSummaryWithoutAnImportCheckpoint(): void
    {
        $definition = AnalyzeAppMetrics::definition();
        $day = new UtcDate('2020-01-01');
        $now = AnalyzeAppMetrics::now();
        $values = AnalyzeAppMetrics::summaryValues($day);
        $values[0] = new ApplicationUserCount('5', new MetricProvenance($definition, $day, $now, ['sampled']));
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create($values[0], $values[1], $values[2]);
        (new AppDailySummaryRepository())->save($summary);
        $report = ($this->app()->make(GetAppDailySummaryReport::class))->process(new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day, $now)))->applicationUsers();
        self::assertSame('5.000000000', $report->total());
        self::assertSame('5.000000000', $report->days()[0]['value']);
        self::assertSame('measured', $report->days()[0]['availability']);
        self::assertSame(['sampled'], $report->days()[0]['quality']);
    }

    public function testReplacingOtherMetricMetadataRetainsOneDailySummary(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $definition = $values[0]->provenance()->definition();
        $factory = new AppDailySummaryFactory(new UuidGenerator());
        $appDailySummaryRepository = new AppDailySummaryRepository();
        $appDailySummaryRepository->save($factory->create($values[0], $values[1], $values[2]));
        $other = AnalyzeAppMetrics::definition(Metric::REGISTERED_USERS, 'alternate');
        $values[0] = new ApplicationUserCount('9', new MetricProvenance($definition, $day, AnalyzeAppMetrics::now()));
        $values[1] = new RegisteredUserSnapshot('11', $day->next()->instant(), new MetricProvenance($other, $day, AnalyzeAppMetrics::now()));
        $summary = $appDailySummaryRepository->findByDay($day);
        self::assertNotNull($summary);
        $summary->replaceAnalysis($values[0], $values[1], $values[2], regions: $summary->regions());
        $appDailySummaryRepository->save($summary);
        $input = new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day, AnalyzeAppMetrics::now()));
        self::assertSame('9.000000000', ($this->app()->make(GetAppDailySummaryReport::class))->process($input)->applicationUsers()->total());
    }

    public function testReadModelDoesNotRequireEntityProvenanceReconstruction(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $row = SummaryModel::query()->firstOrFail();
        $measurements = $row->measurements;
        $measurements[Metric::APPLICATION_USERS->value]['updated_at'] = 'not-a-domain-timestamp';
        $row->forceFill(['measurements' => $measurements])->save();
        $report = ($this->app()->make(GetAppDailySummaryReport::class))->process(new GetAppDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day, AnalyzeAppMetrics::now())))->applicationUsers();
        self::assertSame('5.000000000', $report->total());
        self::assertSame('measured', $report->days()[0]['availability']);
        self::assertNotNull($report->days()[0]['updatedAt']);
    }
}
