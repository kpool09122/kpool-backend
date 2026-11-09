<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport\GetWikiRegionalDailySummaryReportInput;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiRegionalDailySummaryReport;
use Source\Shared\Domain\ValueObject\CountryCode;
use Tests\Helper\AnalyzeSourceRecords;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\TestCase;

#[Group('useDb')]
class WikiRegionalOperationReportTest extends TestCase
{
    public function testCnHistoryUsesTheSameCanonicalCodeAsGa4AndRegistration(): void
    {
        $history = AnalyzeSourceRecords::history('publish', '2020-01-01', 'CN');
        $history->update(['visitor_region' => 'BJ']);
        $input = new GetWikiRegionalDailySummaryReportInput(CreateAnalyzePrincipal::create()->principalIdentifier(), new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now()), new RegionalSelection(CountryCode::CHINA, 'CN-11'));
        $publications = $this->app()->make(GetWikiRegionalDailySummaryReport::class)->process($input)->toArray()['publications'];
        self::assertSame('1', $publications['total']);
        self::assertSame('CN-11', $publications['regions'][0]['region']);
    }

    public function testPhParentAndProvinceAreExactGrainsRatherThanSyntheticRollups(): void
    {
        $parent = AnalyzeSourceRecords::history('publish', '2020-01-01', 'PH');
        $parent->update(['visitor_region' => '40']);
        $province = AnalyzeSourceRecords::history('publish', '2020-01-01', 'PH');
        $province->update(['visitor_region' => 'CAV', 'actor_id' => $parent->actor_id]);
        $principal = CreateAnalyzePrincipal::create()->principalIdentifier();
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now());
        $input = new GetWikiRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::PHILIPPINES));
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $country = $this->app()->make(GetWikiRegionalDailySummaryReport::class)->process($input)->toArray()['publications'];
            $historyQueries = array_filter(DB::getQueryLog(), static fn (array $entry): bool => str_contains($entry['query'], 'wiki_histories'));
            self::assertCount(1, $historyQueries);
        } finally {
            DB::disableQueryLog();
        }
        self::assertSame('2', $country['total']);
        self::assertSame('1', $country['actorCount']);
        self::assertSame(['PH-40', 'PH-CAV'], array_column($country['regions'], 'region'));
        $selected = new GetWikiRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::PHILIPPINES, 'PH-40'));
        $region = $this->app()->make(GetWikiRegionalDailySummaryReport::class)->process($selected)->toArray()['publications'];
        self::assertSame('1', $region['total']);
    }

    public function testActorCountsArePeriodDistinctRatherThanSumOfRegionsAndDays(): void
    {
        $first = AnalyzeSourceRecords::history('draft_status_change', '2020-01-01');
        $first->update(['visitor_region' => '13']);
        $second = AnalyzeSourceRecords::history('draft_status_change', '2020-01-02');
        $second->update(['visitor_region' => '27', 'actor_id' => $first->actor_id]);
        $input = new GetWikiRegionalDailySummaryReportInput(CreateAnalyzePrincipal::create()->principalIdentifier(), new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-02'), AnalyzeWikiMetrics::now()), new RegionalSelection(CountryCode::JAPAN));
        $submissions = $this->app()->make(GetWikiRegionalDailySummaryReport::class)->process($input)->toArray()['submissions'];
        self::assertSame('2', $submissions['total']);
        self::assertSame('1', $submissions['actorCount']);
        self::assertSame(['JP-13', 'JP-27'], array_column($submissions['regions'], 'region'));
        self::assertSame(['1', '1'], array_column($submissions['regions'], 'actorCount'));
    }
}
