<?php

declare(strict_types=1);

namespace Tests\Application\Console\Commands;

use Application\Console\Commands\ImportWikiDailySummaryCommand;
use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryDataset;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInterface;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInput;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiDailySummaryReport;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\TestCase;

#[Group('useDb')]
class ImportWikiDailySummaryCommandTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized (setUp で初期化) */
    private PrincipalIdentifier $principalIdentifier;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->principalIdentifier = CreateAnalyzePrincipal::create()->principalIdentifier();
        Exceptions::fake();
        config(['analyze.reimport_days' => 3, 'analyze.max_pages_per_day' => 1000]);
    }

    public function testEmptyCompleteImportDoesNotInventMeasurementsWithoutSummaryRows(): void
    {
        $service = $this->registerFetchService([]);
        $service->shouldReceive('fetchSummary')->once()->andReturn(AnalyzeWikiMetrics::dataset(new UtcDate('2012-02-29'), [], []));
        self::assertSame(0, $this->runDay());
        $day = new UtcDate('2012-02-29');
        $input = new GetWikiDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day->next(), AnalyzeWikiMetrics::now()));
        $query = $this->app()->make(GetWikiDailySummaryReport::class);
        $report = $query->process($input)->views();
        self::assertSame(0, SummaryModel::query()->count());
        self::assertSame(['not_collected', 'not_collected'], array_column($report->days(), 'availability'));
        self::assertSame([null, null], array_column($report->days(), 'value'));
        self::assertNull($report->days()[0]['updatedAt']);
        self::assertArrayNotHasKey('attemptedAt', $report->days()[0]);
        $revision = $report->revision();
        $service = $this->registerFetchService([]);
        $service->shouldReceive('fetchSummary')->once()->andThrow(new MetricsFetchFailedException());
        self::assertSame(1, $this->runDay());
        $report = $query->process($input)->views();
        Exceptions::assertReported(MetricsFetchFailedException::class);
        Exceptions::assertReportedCount(1);
        self::assertSame('not_collected', $report->days()[0]['availability']);
        self::assertNull($report->days()[0]['value']);
        self::assertSame($revision, $report->revision());
        self::assertSame('measured', $query->process($input)->submissions()->days()[0]['availability']);
    }

    public function testHistoricalReimportIsIdempotentAndPreservesTheEntityIdentifier(): void
    {
        $this->registerFetchService(['2012-02-29', '2012-02-29']);
        self::assertSame(0, $this->runDay());
        $id = SummaryModel::query()->firstOrFail()->id;
        self::assertSame(0, $this->runDay());
        self::assertSame(1, SummaryModel::query()->count());
        self::assertSame($id, SummaryModel::query()->firstOrFail()->id);
    }

    public function testAcquisitionIsInsideTheTransactionAndFailureRetainsThePreviousResult(): void
    {
        $this->registerFetchService(['2012-02-29']);
        self::assertSame(0, $this->runDay());
        $row = SummaryModel::query()->firstOrFail();
        $day = new UtcDate('2012-02-29');
        $query = $this->app()->make(GetWikiDailySummaryReport::class);
        $input = new GetWikiDailySummaryReportInput($this->principalIdentifier, new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()));
        $before = $query->process($input)->toArray();
        $outside = DB::transactionLevel();
        $inside = null;
        $service = $this->registerFetchService([]);
        $service->shouldReceive('fetchSummary')->once()->andReturnUsing(static function () use (&$inside): WikiDailySummaryDataset {
            $inside = DB::transactionLevel();

            throw new MetricsFetchFailedException();
        });
        self::assertSame(1, $this->runDay());
        self::assertNotNull($inside);
        self::assertGreaterThan($outside, $inside);
        self::assertSame($row->id, SummaryModel::query()->firstOrFail()->id);
        self::assertSame($row->revision, SummaryModel::query()->firstOrFail()->revision);
        Exceptions::assertReported(MetricsFetchFailedException::class);
        Exceptions::assertReportedCount(1);
        self::assertSame($before, $query->process($input)->toArray());
        self::assertSame('measured', $query->process($input)->views()->days()[0]['availability']);
        self::assertArrayNotHasKey('attemptedAt', $query->process($input)->views()->days()[0]);
        $this->registerFetchService(['2012-02-29'], '9');
        self::assertSame(0, $this->runDay());
        self::assertSame($row->id, SummaryModel::query()->firstOrFail()->id);
        self::assertSame('9.000000000', SummaryModel::query()->firstOrFail()->views);
    }

    public function testSaveFailureRollsBackTheWholeDayAndNextExecutionStartsAgain(): void
    {
        $this->registerFetchService(['2012-02-29'], '0', true);
        self::assertSame(0, $this->runDay());
        $ids = SummaryModel::query()->orderBy('id')->pluck('id')->all();
        $revisions = SummaryModel::query()->orderBy('id')->pluck('revision')->all();
        $this->registerFetchService(['2012-02-29'], '9', true);
        $writes = 0;
        SummaryModel::saving(static function () use (&$writes): void {
            if (++$writes === 2) {
                throw new RuntimeException('Injected save failure');
            }
        });

        try {
            self::assertSame(1, $this->runDay());
        } finally {
            SummaryModel::flushEventListeners();
        }
        Exceptions::assertReported(RuntimeException::class);
        Exceptions::assertReportedCount(1);
        self::assertSame($ids, SummaryModel::query()->orderBy('id')->pluck('id')->all());
        self::assertSame($revisions, SummaryModel::query()->orderBy('id')->pluck('revision')->all());
        foreach (SummaryModel::query()->get() as $row) {
            self::assertSame('0.000000000', $row->views);
        }
        $this->registerFetchService(['2012-02-29'], '9', true);
        self::assertSame(0, $this->runDay());
        self::assertSame(2, SummaryModel::query()->count());
    }

    public function testInitialFailureIsReportedAndLaterDaysContinueBeforeManualRetry(): void
    {
        $service = $this->registerFetchService(['2012-03-01']);
        $exception = new MetricsFetchFailedException();
        $service->shouldReceive('fetchSummary')->once()
            ->withArgs(static fn (UtcDate $day): bool => (string) $day === '2012-02-29')
            ->andThrow($exception);
        self::assertSame(1, Artisan::call('analyze:import-wiki-daily-summary', ['--from' => '2012-02-29', '--to' => '2012-03-01']));
        Exceptions::assertReported(static fn (MetricsFetchFailedException $reported): bool => $reported === $exception);
        Exceptions::assertReportedCount(1);
        self::assertSame(['2012-03-01'], SummaryModel::query()->orderBy('day')->pluck('day')->all());
        $this->registerFetchService(['2012-02-29']);
        self::assertSame(0, $this->runDay());
        self::assertSame(['2012-02-29', '2012-03-01'], SummaryModel::query()->orderBy('day')->pluck('day')->all());
        Exceptions::assertReportedCount(1);
    }

    public function testDefaultWindowEndsAtUtcDMinusThree(): void
    {
        $this->travelTo(AnalyzeWikiMetrics::now());
        config(['analyze.reimport_days' => 2]);
        $this->registerFetchService(['2026-10-29', '2026-10-30']);
        self::assertSame(0, Artisan::call('analyze:import-wiki-daily-summary'));
        $output = Artisan::output();
        self::assertStringContainsString('2026-10-29 complete', $output);
        self::assertStringContainsString('2026-10-30 complete', $output);
        $this->travelBack();
    }

    public function testInvalidRangeAndConfigurationFailBeforeFetching(): void
    {
        $this->registerFetchService([])->shouldNotReceive('fetchSummary');
        self::assertSame(1, Artisan::call('analyze:import-wiki-daily-summary', ['--from' => '2012-02-29']));
        self::assertSame(1, Artisan::call('analyze:import-wiki-daily-summary', ['--from' => 'invalid', '--to' => 'invalid']));
        config(['analyze.max_pages_per_day' => 0]);
        self::assertSame(1, Artisan::call('analyze:import-wiki-daily-summary'));
        Exceptions::assertReportedCount(3);
    }

    /** @impure */
    private function runDay(): int
    {
        return Artisan::call('analyze:import-wiki-daily-summary', ['--from' => '2012-02-29', '--to' => '2012-02-29']);
    }

    /** @param list<string> $dates
     * @return DailyMetricsFetchServiceInterface&MockInterface
     */
    private function registerFetchService(array $dates, string $value = '0', bool $twoTargets = false): DailyMetricsFetchServiceInterface
    {
        /** @var DailyMetricsFetchServiceInterface&MockInterface $service */
        $service = Mockery::mock(DailyMetricsFetchServiceInterface::class);
        foreach ($dates as $date) {
            $service->shouldReceive('fetchSummary')->once()->withArgs(static fn (UtcDate $day, DateTimeImmutable $now, int $maxPages): bool => (string) $day === $date)
                ->andReturnUsing(static fn (UtcDate $day): WikiDailySummaryDataset => AnalyzeWikiMetrics::dataset($day, ['wiki_views' => $value, 'wiki_viewers' => $value, 'wiki_engagement_seconds' => $value, 'wiki_submissions' => $value, 'wiki_publications' => $value], $twoTargets ? ['69200000-0000-7000-8000-000000000001', '69200000-0000-7000-8000-000000000013'] : ['69200000-0000-7000-8000-000000000001']))->byDefault();
        }
        $this->app()->instance(DailyMetricsFetchServiceInterface::class, $service);
        $this->app()->forgetInstance(ImportWikiDailySummaryInterface::class);
        Artisan::registerCommand(new ImportWikiDailySummaryCommand());

        return $service;
    }
}
