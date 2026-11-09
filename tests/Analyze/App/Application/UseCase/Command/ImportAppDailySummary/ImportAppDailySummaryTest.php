<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

use Application\Models\Analyze\App\AppDailySummary as SummaryModel;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\App\Application\Service\AppDailySummaryData;
use Source\Analyze\App\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummary;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryInput;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryOutput;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\TestCase;

#[Group('useDb')]
class ImportAppDailySummaryTest extends TestCase
{
    public function testFailureDiscardsTheAttemptAndNextExecutionFetchesAgain(): void
    {
        $day = new UtcDate('2020-01-01');
        $service = $this->service();
        $attempt = 0;
        $now = AnalyzeAppMetrics::now();
        $service->shouldReceive('fetchSummary')->twice()->with($day, $now, 1000)->andReturnUsing(static function () use (&$attempt, $day): AppDailySummaryData {
            if (++$attempt === 1) {
                throw new MetricsFetchFailedException();
            }

            return AnalyzeAppMetrics::data($day, ['application_users' => '9']);
        });
        $usecase = $this->usecase($service);
        $input = new ImportAppDailySummaryInput($day, $now);

        try {
            DB::transaction(fn () => $usecase->process($input, new ImportAppDailySummaryOutput()));
            self::fail('Expected acquisition failure.');
        } catch (MetricsFetchFailedException) {
            self::assertSame(0, SummaryModel::query()->count());
        }
        $output = new ImportAppDailySummaryOutput();
        DB::transaction(fn () => $usecase->process($input, $output));
        self::assertSame(1, $output->summaryCount());
        $repository = new AppDailySummaryRepository();
        $summary = $repository->findByDay($day);
        self::assertNotNull($summary);
        self::assertSame('9', $summary->applicationUsers()->count());
    }

    public function testIncompleteAcquisitionCannotProduceAPartialSummary(): void
    {
        $day = new UtcDate('2020-01-01');
        $service = $this->service();
        $service->shouldReceive('fetchSummary')->once()->andThrow(new IncompleteDailyMetricsException());

        try {
            $this->usecase($service)->process(new ImportAppDailySummaryInput($day, AnalyzeAppMetrics::now()), new ImportAppDailySummaryOutput());
            self::fail('Expected incomplete acquisition.');
        } catch (IncompleteDailyMetricsException) {
            self::assertSame(0, SummaryModel::query()->count());
        }
    }

    public function testReimportUpdatesTheExistingEntity(): void
    {
        $day = new UtcDate('2020-01-01');
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create(...AnalyzeAppMetrics::summaryValues($day));
        (new AppDailySummaryRepository())->save($summary);
        $service = $this->service();
        $service->shouldReceive('fetchSummary')->once()->andReturn(AnalyzeAppMetrics::data($day, ['application_users' => '9']));
        $this->usecase($service)->process(new ImportAppDailySummaryInput($day, AnalyzeAppMetrics::now()), new ImportAppDailySummaryOutput());
        self::assertSame(1, SummaryModel::query()->count());
        self::assertSame((string) $summary->identifier(), SummaryModel::query()->firstOrFail()->id);
        self::assertSame('9.000000000', SummaryModel::query()->firstOrFail()->application_users);
    }

    /** @return DailyMetricsFetchServiceInterface&MockInterface */
    private function service(): DailyMetricsFetchServiceInterface
    {
        /** @var DailyMetricsFetchServiceInterface&MockInterface $service */
        $service = Mockery::mock(DailyMetricsFetchServiceInterface::class);

        return $service;
    }

    private function usecase(DailyMetricsFetchServiceInterface $service): ImportAppDailySummary
    {
        return new ImportAppDailySummary(new AppDailySummaryRepository(), new AppDailySummaryFactory(new UuidGenerator()), $service);
    }
}
