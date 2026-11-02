<?php

declare(strict_types=1);

namespace Tests\Application\Console\Commands;

use Application\Console\Commands\ImportAppDailySummaryCommand;
use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\App\Application\Service\AppDailySummaryData;
use Source\Analyze\App\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryInterface;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\TestCase;

#[Group('useDb')]
class ImportAppDailySummaryCommandTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        config(['analyze.reimport_days' => 3, 'analyze.max_pages_per_day' => 1000]);
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
        $outside = DB::transactionLevel();
        $inside = null;
        $service = $this->registerFetchService([]);
        $service->shouldReceive('fetchSummary')->once()->andReturnUsing(static function () use (&$inside): AppDailySummaryData {
            $inside = DB::transactionLevel();

            throw new MetricsFetchFailedException();
        });
        self::assertSame(1, $this->runDay());
        self::assertNotNull($inside);
        self::assertGreaterThan($outside, $inside);
        self::assertSame($row->id, SummaryModel::query()->firstOrFail()->id);
        self::assertSame($row->revision, SummaryModel::query()->firstOrFail()->revision);
        $this->registerFetchService(['2012-02-29'], '9');
        self::assertSame(0, $this->runDay());
        self::assertSame($row->id, SummaryModel::query()->firstOrFail()->id);
        self::assertSame('9.000000000', SummaryModel::query()->firstOrFail()->application_users);
    }

    public function testSaveFailureRollsBackTheWholeDayAndNextExecutionStartsAgain(): void
    {
        $this->registerFetchService(['2012-02-29'], '0');
        self::assertSame(0, $this->runDay());
        $ids = SummaryModel::query()->orderBy('id')->pluck('id')->all();
        $revisions = SummaryModel::query()->orderBy('id')->pluck('revision')->all();
        $this->registerFetchService(['2012-02-29'], '9');
        $writes = 0;
        SummaryModel::saving(static function () use (&$writes): void {
            if (++$writes === 1) {
                throw new RuntimeException('Injected save failure');
            }
        });

        try {
            self::assertSame(1, $this->runDay());
        } finally {
            SummaryModel::flushEventListeners();
        }
        self::assertSame($ids, SummaryModel::query()->orderBy('id')->pluck('id')->all());
        self::assertSame($revisions, SummaryModel::query()->orderBy('id')->pluck('revision')->all());
        foreach (SummaryModel::query()->get() as $row) {
            self::assertSame('0.000000000', $row->application_users);
        }
        $this->registerFetchService(['2012-02-29'], '9');
        self::assertSame(0, $this->runDay());
        self::assertSame(1, SummaryModel::query()->count());
    }

    public function testDefaultWindowEndsAtUtcDMinusThree(): void
    {
        $this->travelTo(AnalyzeAppMetrics::now());
        config(['analyze.reimport_days' => 2]);
        $this->registerFetchService(['2026-10-29', '2026-10-30']);
        self::assertSame(0, Artisan::call('analyze:import-app-daily-summary'));
        $output = Artisan::output();
        self::assertStringContainsString('2026-10-29 complete', $output);
        self::assertStringContainsString('2026-10-30 complete', $output);
        $this->travelBack();
    }

    public function testInvalidRangeAndConfigurationFailBeforeFetching(): void
    {
        $this->registerFetchService([])->shouldNotReceive('fetchSummary');
        self::assertSame(1, Artisan::call('analyze:import-app-daily-summary', ['--from' => '2012-02-29']));
        self::assertSame(1, Artisan::call('analyze:import-app-daily-summary', ['--from' => 'invalid', '--to' => 'invalid']));
        config(['analyze.max_pages_per_day' => 0]);
        self::assertSame(1, Artisan::call('analyze:import-app-daily-summary'));
    }

    /** @impure */
    private function runDay(): int
    {
        return Artisan::call('analyze:import-app-daily-summary', ['--from' => '2012-02-29', '--to' => '2012-02-29']);
    }

    /** @param list<string> $dates
     * @return DailyMetricsFetchServiceInterface&MockInterface
     */
    private function registerFetchService(array $dates, string $value = '0'): DailyMetricsFetchServiceInterface
    {
        /** @var DailyMetricsFetchServiceInterface&MockInterface $service */
        $service = Mockery::mock(DailyMetricsFetchServiceInterface::class);
        foreach ($dates as $date) {
            $service->shouldReceive('fetchSummary')->once()->withArgs(static fn (UtcDate $day, DateTimeImmutable $now, int $maxPages): bool => (string) $day === $date)
                ->andReturnUsing(static fn (UtcDate $day): AppDailySummaryData => AnalyzeAppMetrics::data($day, ['application_users' => $value, 'registered_users' => $value]))->byDefault();
        }
        $this->app()->instance(DailyMetricsFetchServiceInterface::class, $service);
        $this->app()->forgetInstance(ImportAppDailySummaryInterface::class);
        Artisan::registerCommand(new ImportAppDailySummaryCommand());

        return $service;
    }
}
