<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Carbon\CarbonImmutable;
use Database\Seeders\AnalyzeRegionalSampleSeeder;
use Database\Seeders\AnalyzeSampleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInput;
use Source\Analyze\App\Infrastructure\Query\GetAppRegionalDailySummaryReport;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport\GetWikiRegionalDailySummaryReportInput;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiRegionalDailySummaryReport;
use Source\Shared\Domain\ValueObject\CountryCode;
use Symfony\Component\Uid\Uuid;
use Tests\TestCase;

#[Group('useDb')]
class AnalyzeSampleSeederTest extends TestCase
{
    public function testRegionalSamplesAreQueryableAndRepeatableWithoutReplacingImportedData(): void
    {
        $now = CarbonImmutable::now('UTC');
        $end = $now->startOfDay()->subDays(3);
        $start = $end->subDays(27);
        $importRevision = (string) Uuid::v7();
        DB::table('analyze_regional_daily_summaries')->insert([
            'context' => 'app', 'day' => $end->toDateString(), 'country' => 'JP', 'region' => 'JP-13',
            'metric' => 'application_users', 'source' => 'ga4', 'value' => '9999', 'quality' => '[]',
            'revision' => $importRevision, 'updated_at' => $now,
        ]);
        $this->seed(AnalyzeSampleSeeder::class);
        $principalId = DB::table('analyze_principals')->value('id');
        self::assertIsString($principalId);
        $principal = new PrincipalIdentifier($principalId);
        $period = new ReportingPeriod(new UtcDate($start->toDateString()), new UtcDate($end->toDateString()), $now);
        $countries = DB::table('analyze_regional_daily_summaries')->distinct()->orderBy('country')->pluck('country')->all();
        self::assertSame(['CN', 'JP', 'US'], $countries);

        foreach (['JP' => 'JP-13', 'US' => 'US-CA', 'CN' => 'CN-11'] as $country => $region) {
            $selection = new RegionalSelection(CountryCode::from($country), $region);
            $app = $this->app()->make(GetAppRegionalDailySummaryReport::class)->process(new GetAppRegionalDailySummaryReportInput($principal, $period, $selection))->toArray();
            $wiki = $this->app()->make(GetWikiRegionalDailySummaryReport::class)->process(new GetWikiRegionalDailySummaryReportInput($principal, $period, $selection, language: 'ja'))->toArray();
            self::assertCount(28, $app['applicationUsers']['days']);
            self::assertSame('not_collected', $app['applicationUsers']['days'][3]['availability']);
            self::assertSame('not_collected', $app['applicationUsers']['days'][9]['availability']);
            self::assertNotNull($app['registeredUsers']['total']);
            self::assertNotNull($wiki['views']['total']);
            self::assertGreaterThan(0, $wiki['engagementTime']['denominator']);
            self::assertCount(28, $wiki['views']['days']);
            self::assertGreaterThan(0, (int) $wiki['submissions']['total']);
            self::assertGreaterThan(0, (int) $wiki['publications']['total']);
            $regions = DB::table('analyze_regional_daily_summaries')->where('country', $country)->distinct()->pluck('region')->all();
            $expected = AnalyzeRegionalSampleSeeder::regions($country);
            sort($regions);
            sort($expected);
            self::assertSame($expected, $regions);
        }
        $count = DB::table('analyze_regional_daily_summaries')->count();
        $historyCount = DB::table('wiki_histories')->count();
        // Advance the rolling window to ensure old regional samples are also removed.
        CarbonImmutable::setTestNow($now->addDay());

        try {
            $this->seed(AnalyzeSampleSeeder::class);
        } finally {
            CarbonImmutable::setTestNow();
        }
        self::assertSame($count, DB::table('analyze_regional_daily_summaries')->count());
        self::assertSame($historyCount, DB::table('wiki_histories')->count());
        self::assertSame('9999.000000000', DB::table('analyze_regional_daily_summaries')->where('revision', $importRevision)->value('value'));
        self::assertFalse(DB::table('analyze_regional_daily_summaries')->where('day', $start->toDateString())->exists());
    }
}
