<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Service;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Client\MockGoogleAnalyticsClient;
use Source\Analyze\Wiki\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\Wiki\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Wiki\Application\Exception\WikiOperationHistoryUnavailableException;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationCount;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationsQueryServiceInterface;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Infrastructure\Service\DailyMetricsFetchService;
use Source\Shared\Domain\ValueObject\CountryCode;
use Tests\Helper\AnalyticsHttpStub;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\TestCase;

class DailyMetricsFetchServiceTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://example.test']);
    }

    public function testAcquiresAllWikiMetricsAndReadsOperationHistoryOnlyOnce(): void
    {
        config(['app.frontend_url' => 'https://public.example.test:8443/wiki/test?lang=ja']);
        $http = new AnalyticsHttpStub();
        $day = new UtcDate('2020-01-01');
        /** @var DailyWikiOperationsQueryServiceInterface&MockInterface $query */
        $query = Mockery::mock(DailyWikiOperationsQueryServiceInterface::class);
        $query->shouldReceive('find')->once()->withArgs(static fn (DateTimeImmutable $start, DateTimeImmutable $end): bool => $start == $day->instant() && $end == $day->next()->instant())
            ->andReturn($this->operationCounts());
        $data = (new DailyMetricsFetchService($http->client(), $query))->fetchSummary($day, AnalyzeWikiMetrics::now(), 10)->remaining();
        self::assertCount(1, $data);
        self::assertSame('ga4', $data[0]->views()->provenance()->definition()->source);
        self::assertSame('ga4', $data[0]->viewers()->provenance()->definition()->source);
        self::assertSame('ga4', $data[0]->engagementTime()->provenance()->definition()->source);
        self::assertSame('120', $data[0]->views()->count());
        self::assertSame('60', $data[0]->viewers()->count());
        self::assertSame('1800.250000001', $data[0]->engagementTime()->totalSeconds());
        self::assertSame(60, $data[0]->engagementTime()->sampleCount());
        self::assertSame('2', $data[0]->submissions()->count());
        self::assertSame('1', $data[0]->publications()->count());
        self::assertCount(3, $http->requests);
        self::assertStringContainsString('page_view', (string) $http->requests[0]->getBody());
        self::assertStringContainsString('page_view', (string) $http->requests[1]->getBody());
        self::assertStringNotContainsString('page_view', (string) $http->requests[2]->getBody());
        self::assertStringContainsString('pagePath', (string) $http->requests[2]->getBody());
        foreach ($http->requests as $request) {
            self::assertStringContainsString(json_encode('^/wiki/.*$', JSON_THROW_ON_ERROR), (string) $request->getBody());
            self::assertStringContainsString('FULL_REGEXP', (string) $request->getBody());
            self::assertStringContainsString('public.example.test', (string) $request->getBody());
            self::assertStringNotContainsString('8443', (string) $request->getBody());
            self::assertStringNotContainsString('lang=ja', (string) $request->getBody());
        }
    }

    public function testFetchesEveryPageAndZerosOnlyMissingTargetsFromCompleteReports(): void
    {
        $http = new AnalyticsHttpStub();
        $http->paginate = true;
        $data = $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 2)->remaining();
        self::assertCount(2, $data);
        self::assertCount(6, $http->requests);
        self::assertSame(CountryCode::JAPAN, $data[0]->country());
        self::assertSame(CountryCode::UNITED_STATES, $data[1]->country());
        self::assertSame('0', $data[1]->submissions()->count());
        self::assertStringContainsString('"offset":"1"', (string) $http->requests[1]->getBody());
    }

    public function testHistoryOnlyTargetsReceiveMeasuredZeroAnalytics(): void
    {
        $http = new AnalyticsHttpStub();
        $http->empty = true;
        $data = $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 1)->remaining();
        self::assertCount(1, $data);
        self::assertSame('0', $data[0]->views()->count());
        self::assertSame('0', $data[0]->engagementTime()->totalSeconds());
        self::assertSame(0, $data[0]->engagementTime()->sampleCount());
        self::assertSame('2', $data[0]->submissions()->count());
    }

    public function testUnresolvedHistoryFailsAndAnotherExecutionFetchesAllReportsAgain(): void
    {
        $http = new AnalyticsHttpStub();
        $day = new UtcDate('2020-01-01');
        $attempt = 0;
        $counts = $this->operationCounts();
        /** @var DailyWikiOperationsQueryServiceInterface&MockInterface $query */
        $query = Mockery::mock(DailyWikiOperationsQueryServiceInterface::class);
        $query->shouldReceive('find')->twice()->andReturnUsing(static function () use (&$attempt, $counts): array {
            if (++$attempt === 1) {
                throw new WikiOperationHistoryUnavailableException();
            }

            return $counts;
        });
        $service = new DailyMetricsFetchService($http->client(), $query);

        try {
            $service->fetchSummary($day, AnalyzeWikiMetrics::now(), 10);
            self::fail('Expected unavailable history.');
        } catch (MetricsFetchFailedException) {
            self::assertCount(3, $http->requests);
        }
        self::assertCount(1, $service->fetchSummary($day, AnalyzeWikiMetrics::now(), 10)->remaining());
        self::assertCount(6, $http->requests);
    }

    public function testPageLimitCannotPublishAPartialDataset(): void
    {
        $http = new AnalyticsHttpStub();
        $http->paginate = true;
        $this->expectException(IncompleteDailyMetricsException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 1);
    }

    /** @return iterable<string, array{string}> */
    public static function brokenPagination(): iterable
    {
        yield 'changed total' => ['total'];
        yield 'empty continuation' => ['empty'];
    }

    #[DataProvider('brokenPagination')]
    public function testIncompletePaginationIsRejected(string $failure): void
    {
        $http = new AnalyticsHttpStub();
        $http->paginate = true;
        if ($failure === 'total') {
            $http->changingTotal = true;
        } else {
            $http->emptyContinuation = true;
        }
        $this->expectException(IncompleteDailyMetricsException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 10);
    }

    public function testDuplicateTargetsAcrossPagesAreRejected(): void
    {
        $http = new AnalyticsHttpStub();
        $http->paginate = true;
        $http->dimensionOverrides = ['countryId' => 'JP'];
        $this->expectException(InvalidDailySummaryException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 10);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidAttribution(): iterable
    {
        yield 'Wiki' => ['customEvent:wiki_id', '(not set)'];
        yield 'translation' => ['customEvent:wiki_translation_set_id', ''];
        yield 'language' => ['customEvent:wiki_language', '(not set)'];
        yield 'denominator' => ['totalUsers', '99999999999999999999'];
    }

    #[DataProvider('invalidAttribution')]
    public function testInvalidAttributionOrDenominatorIsRejected(string $field, string $value): void
    {
        $http = new AnalyticsHttpStub();
        if ($field === 'totalUsers') {
            $http->metricOverrides[$field] = $value;
        } else {
            $http->dimensionOverrides[$field] = $value;
        }
        $this->expectException(IncompleteDailyMetricsException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 10);
    }

    public function testNonUtcPropertyCannotProduceASummary(): void
    {
        $http = new AnalyticsHttpStub();
        $http->metadata = ['timeZone' => 'Asia/Tokyo'];
        $this->expectException(IncompleteDailyMetricsException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 10);
    }

    public function testApiQualityFailureIsNotConvertedToSuccess(): void
    {
        $http = new AnalyticsHttpStub();
        $http->metadata = ['timeZone' => 'UTC', 'subjectToThresholding' => true];
        $this->expectException(MetricsFetchFailedException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 10);
    }

    public function testHistoricalDayNeedsNoStartDateConfiguration(): void
    {
        $this->app()->instance('env', 'production');
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $dataset = $this->service($http)->fetchSummary(new UtcDate('2009-12-31'), AnalyzeWikiMetrics::now(), 1);
        self::assertCount(3, $http->requests);
        self::assertNotEmpty($dataset->remaining());
    }

    public function testInvalidFrontendUrlDoesNotCallTheApi(): void
    {
        config(['app.frontend_url' => 'not-a-url']);
        $http = new AnalyticsHttpStub();

        try {
            $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), 1);
            self::fail('An invalid frontend URL must be rejected.');
        } catch (MetricsFetchFailedException) {
            self::assertSame([], $http->requests);
        }
    }

    private function service(AnalyticsHttpStub $http): DailyMetricsFetchService
    {
        /** @var DailyWikiOperationsQueryServiceInterface&MockInterface $query */
        $query = Mockery::mock(DailyWikiOperationsQueryServiceInterface::class);
        $query->shouldReceive('find')->andReturn($this->operationCounts());

        return new DailyMetricsFetchService($http->client(), $query);
    }

    /** @return array{DailyWikiOperationCount} */
    private function operationCounts(): array
    {
        $count = new DailyWikiOperationCount(MockGoogleAnalyticsClient::WIKI_IDENTIFIER, MockGoogleAnalyticsClient::TRANSLATION_SET_IDENTIFIER, 'ja', 'JP');
        $count->recordSubmission();
        $count->recordSubmission();
        $count->recordPublication();

        return [$count];
    }
}
