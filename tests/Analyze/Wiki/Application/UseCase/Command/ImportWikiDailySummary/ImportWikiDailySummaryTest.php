<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use Application\Models\Analyze\RegionalDailySummary;
use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\Wiki\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryDataset;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummary;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInput;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryOutput;
use Source\Analyze\Wiki\Infrastructure\Factory\WikiDailySummaryFactory;
use Source\Analyze\Wiki\Infrastructure\Repository\WikiDailySummaryRepository;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\TestCase;

#[Group('useDb')]
class ImportWikiDailySummaryTest extends TestCase
{
    public function testFailureDiscardsTheAttemptAndNextExecutionFetchesAgain(): void
    {
        $day = new UtcDate('2020-01-01');
        $service = $this->service();
        $attempt = 0;
        $now = AnalyzeWikiMetrics::now();
        $service->shouldReceive('fetchSummary')->twice()->with($day, $now, 1000)->andReturnUsing(static function () use (&$attempt, $day): WikiDailySummaryDataset {
            if (++$attempt === 1) {
                throw new MetricsFetchFailedException();
            }

            return AnalyzeWikiMetrics::dataset($day, ['wiki_views' => '9']);
        });
        $usecase = $this->usecase($service);
        $input = new ImportWikiDailySummaryInput($day, $now);

        try {
            DB::transaction(fn () => $usecase->process($input, new ImportWikiDailySummaryOutput()));
            self::fail('Expected acquisition failure.');
        } catch (MetricsFetchFailedException) {
            self::assertSame(0, SummaryModel::query()->count());
        }
        $output = new ImportWikiDailySummaryOutput();
        DB::transaction(fn () => $usecase->process($input, $output));
        self::assertSame(1, $output->summaryCount());
        $repository = new WikiDailySummaryRepository();
        $summary = $repository->findAllByDay($day)[0];
        self::assertNotNull($summary);
        self::assertSame('9', $summary->views()->count());
    }

    public function testIncompleteAcquisitionCannotProduceAPartialSummary(): void
    {
        $day = new UtcDate('2020-01-01');
        $service = $this->service();
        $service->shouldReceive('fetchSummary')->once()->andThrow(new IncompleteDailyMetricsException());

        try {
            $this->usecase($service)->process(new ImportWikiDailySummaryInput($day, AnalyzeWikiMetrics::now()), new ImportWikiDailySummaryOutput());
            self::fail('Expected incomplete acquisition.');
        } catch (IncompleteDailyMetricsException) {
            self::assertSame(0, SummaryModel::query()->count());
        }
    }

    public function testCompleteEmptySnapshotReturnsZeroWithoutPersistingAnything(): void
    {
        $day = new UtcDate('2020-01-01');
        $service = $this->service();
        $service->shouldReceive('fetchSummary')->once()->andReturn(AnalyzeWikiMetrics::dataset($day, [], []));
        $output = new ImportWikiDailySummaryOutput();
        $this->usecase($service)->process(new ImportWikiDailySummaryInput($day, AnalyzeWikiMetrics::now()), $output);
        self::assertSame(0, $output->summaryCount());
        self::assertSame(0, SummaryModel::query()->count());
    }

    public function testReimportPreservesFormerTargetsAsMeasuredZeroAndCreatesNewTargets(): void
    {
        $day = new UtcDate('2020-01-01');
        $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(new WikiIdentifier('69200000-0000-7000-8000-000000000012'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, ...AnalyzeWikiMetrics::analysisValues($day));
        $repository = new WikiDailySummaryRepository();
        $repository->save($summary);
        $service = $this->service();
        $service->shouldReceive('fetchSummary')->once()->andReturn(AnalyzeWikiMetrics::dataset($day));
        $output = new ImportWikiDailySummaryOutput();
        $this->usecase($service)->process(new ImportWikiDailySummaryInput($day, AnalyzeWikiMetrics::now()), $output);
        self::assertSame(2, $output->summaryCount());
        $former = SummaryModel::query()->where('wiki_identifier', '69200000-0000-7000-8000-000000000012')->firstOrFail();
        self::assertSame((string) $summary->identifier(), $former->id);
        self::assertSame('0.000000000', $former->views);
    }

    public function testRegionalOnlyTargetIsOwnedByASummaryAndRemovedAfterAnEmptyImport(): void
    {
        $day = new UtcDate('2020-01-01');
        $service = $this->service();
        $region = new RegionalMetricObservation('JP', 'JP-13', 'views', '12', wikiIdentifier: '69200000-0000-7000-8000-000000000001', translationSetIdentifier: '69200000-0000-7000-8000-000000000002', language: 'ja');
        $service->shouldReceive('fetchSummary')->twice()->andReturn(AnalyzeWikiMetrics::dataset($day, [], [], [$region]), AnalyzeWikiMetrics::dataset($day, [], []));
        $usecase = $this->usecase($service);
        $input = new ImportWikiDailySummaryInput($day, AnalyzeWikiMetrics::now());
        $output = new ImportWikiDailySummaryOutput();
        $usecase->process($input, $output);
        $repository = new WikiDailySummaryRepository();
        $stored = $repository->findAllByDay($day)[0];
        self::assertSame('0', $stored->views()->count());
        self::assertEquals([$region], $stored->regions());
        self::assertSame(1, $output->summaryCount());

        $usecase->process($input, $output);
        self::assertSame([], $repository->findAllByDay($day)[0]->regions());
        self::assertSame(0, RegionalDailySummary::query()->count());
    }

    /** @return DailyMetricsFetchServiceInterface&MockInterface */
    private function service(): DailyMetricsFetchServiceInterface
    {
        /** @var DailyMetricsFetchServiceInterface&MockInterface $service */
        $service = Mockery::mock(DailyMetricsFetchServiceInterface::class);

        return $service;
    }

    private function usecase(DailyMetricsFetchServiceInterface $service): ImportWikiDailySummary
    {
        return new ImportWikiDailySummary(new WikiDailySummaryRepository(), new WikiDailySummaryFactory(new UuidGenerator()), $service);
    }
}
