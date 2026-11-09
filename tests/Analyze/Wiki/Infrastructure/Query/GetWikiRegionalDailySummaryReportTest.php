<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Query;

use Application\Models\Analyze\RegionalDailySummary;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Service\RegionalMetricsFetchService;
use Source\Analyze\Shared\Infrastructure\Service\RegionNormalizer;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummary;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInput;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryOutput;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport\GetWikiRegionalDailySummaryReportInput;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiRegionalDailySummaryReport;
use Source\Analyze\Wiki\Infrastructure\Service\DailyMetricsFetchService;
use Source\Analyze\Wiki\Infrastructure\Service\DailyWikiOperationsQueryService;
use Source\Shared\Domain\ValueObject\CountryCode;
use Symfony\Component\Uid\Uuid;
use Tests\Helper\AnalyticsHttpStub;
use Tests\Helper\AnalyzeSourceRecords;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\TestCase;

#[Group('useDb')]
class GetWikiRegionalDailySummaryReportTest extends TestCase
{
    public function testAllMetricsAndFiltersWithAValidUnmappedCountryAndRealStoredProjection(): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $http->dimensionOverrides = ['countryId' => 'DE', 'region' => 'Berlin'];
        $service = new DailyMetricsFetchService($http->client(), new DailyWikiOperationsQueryService(), new RegionalMetricsFetchService($http->client(), new RegionNormalizer()));
        $day = new UtcDate('2020-01-01');
        $this->app()->instance(DailyMetricsFetchServiceInterface::class, $service);
        DB::transaction(fn () => $this->app()->make(ImportWikiDailySummary::class)->process(new ImportWikiDailySummaryInput($day, AnalyzeWikiMetrics::now()), new ImportWikiDailySummaryOutput()));
        $history = AnalyzeSourceRecords::history('publish', '2020-01-01', 'DE');
        $history->update(['visitor_region' => 'BE']);
        $input = new GetWikiRegionalDailySummaryReportInput(CreateAnalyzePrincipal::create()->principalIdentifier(), new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()), new RegionalSelection(CountryCode::GERMANY, 'DE-BE'), language: 'ja');
        $query = $this->app()->make(GetWikiRegionalDailySummaryReport::class);
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $report = $query->process($input)->toArray();
            $regionalQueries = array_filter(DB::getQueryLog(), static fn (array $entry): bool => str_contains($entry['query'], 'analyze_regional_daily_summaries'));
            self::assertCount(1, $regionalQueries);
            $historyQueries = array_filter(DB::getQueryLog(), static fn (array $entry): bool => str_contains($entry['query'], 'wiki_histories'));
            self::assertCount(1, $historyQueries);
        } finally {
            DB::disableQueryLog();
        }
        self::assertSame('120.000000000', $report['views']['total']);
        self::assertSame('60.000000000', $report['viewers']['total']);
        self::assertSame('1800.250000001', $report['engagementTime']['total']);
        self::assertSame(60, $report['engagementTime']['denominator']);
        self::assertSame('1', $report['publications']['total']);
        self::assertSame('1', $report['publications']['actorCount']);
        self::assertSame('DE-BE', $report['publications']['regions'][0]['region']);
        self::assertFalse($report['views']['regions'][0]['mapSupported']);
        $otherLanguage = new GetWikiRegionalDailySummaryReportInput($input->principalIdentifier(), $input->period(), $input->selection(), language: 'en');
        self::assertNull($query->process($otherLanguage)->toArray()['views']['total']);
        $target = new GetWikiRegionalDailySummaryReportInput($input->principalIdentifier(), $input->period(), $input->selection(), wikiIdentifier: '69200000-0000-7000-8000-000000000001');
        self::assertSame('120.000000000', $query->process($target)->toArray()['views']['total']);
        $translation = new GetWikiRegionalDailySummaryReportInput($input->principalIdentifier(), $input->period(), $input->selection(), translationSetIdentifier: '69200000-0000-7000-8000-000000000002');
        self::assertSame('120.000000000', $query->process($translation)->toArray()['views']['total']);
        RegionalDailySummary::query()->create(['context' => 'wiki', 'day' => '2020-01-01', 'country' => 'DE', 'region' => 'DE-BE', 'wiki_identifier' => (string) Uuid::v7(), 'language' => 'ja', 'metric' => 'engagement_seconds', 'value' => '300.125000001', 'denominator' => 20, 'quality' => [], 'revision' => (string) Uuid::v7(), 'updated_at' => '2020-01-03T00:00:00Z']);
        $combined = $query->process($input)->toArray()['engagementTime'];
        self::assertSame('2100.375000002', $combined['total']);
        self::assertSame(60 + 20, $combined['denominator']);
    }
}
