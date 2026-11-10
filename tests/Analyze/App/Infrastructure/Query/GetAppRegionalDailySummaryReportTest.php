<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Infrastructure\Query;

use Application\Models\Analyze\App\AppCountryDailySummary;
use Application\Models\Analyze\RegionalDailySummary;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport\GetAppDailySummaryReportInput;
use Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport\GetAppRegionalDailySummaryReportInput;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Query\GetAppDailySummaryReport;
use Source\Analyze\App\Infrastructure\Query\GetAppRegionalDailySummaryReport;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Symfony\Component\Uid\Uuid;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\TestCase;

#[Group('useDb')]
class GetAppRegionalDailySummaryReportTest extends TestCase
{
    public function testUnsupportedMapRegionIsQueryableWithTrendsAndFinalSnapshot(): void
    {
        $principal = CreateAnalyzePrincipal::create()->principalIdentifier();
        foreach (['2020-01-01', '2020-01-02'] as $i => $day) {
            foreach (['application_users' => '5', 'registered_users' => (string) (10 + $i)] as $metric => $value) {
                RegionalDailySummary::query()->create(['context' => 'app', 'day' => $day, 'country' => 'DE', 'region' => 'DE-BE', 'metric' => $metric, 'source' => $metric === 'registered_users' ? 'identity_history' : 'ga4', 'value' => $value, 'quality' => [], 'revision' => (string) Uuid::v7(), 'updated_at' => '2020-01-03T00:00:00Z']);
            }
        }
        $input = new GetAppRegionalDailySummaryReportInput($principal, new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-02'), AnalyzeAppMetrics::now()), new RegionalSelection(CountryCode::GERMANY, 'DE-BE'));
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $report = $this->app()->make(GetAppRegionalDailySummaryReport::class)->process($input)->toArray();
            $regionalQueries = array_filter(DB::getQueryLog(), static fn (array $entry): bool => str_contains($entry['query'], 'analyze_regional_daily_summaries'));
            self::assertCount(2, $regionalQueries);
        } finally {
            DB::disableQueryLog();
        }
        self::assertSame('10.000000000', $report['applicationUsers']['total']);
        self::assertSame('11.000000000', $report['registeredUsers']['total']);
        self::assertSame(['5.000000000', '5.000000000'], array_column($report['applicationUsers']['days'], 'value'));
        self::assertSame('DE-BE', $report['applicationUsers']['regions'][0]['region']);
        self::assertFalse($report['applicationUsers']['regions'][0]['mapSupported']);
        self::assertArrayNotHasKey('countries', $report['applicationUsers']);
    }

    public function testCountryTotalsAreIndependentAndLegacyEndpointDoesNotQueryRegions(): void
    {
        $day = new UtcDate('2020-01-01');
        $principal = CreateAnalyzePrincipal::create()->principalIdentifier();
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create(...AnalyzeAppMetrics::summaryValues($day));
        (new AppDailySummaryRepository())->save($summary);
        AppCountryDailySummary::query()->where('day', (string) $day)->delete();
        AppCountryDailySummary::query()->create(['id' => (string) Uuid::v7(), 'day' => (string) $day, 'country' => 'JP', 'application_users' => '80', 'registered_users' => '30']);
        foreach (['JP-13' => '60', 'JP-27' => '40', '' => '0'] as $region => $value) {
            RegionalDailySummary::query()->create(['context' => 'app', 'day' => (string) $day, 'country' => 'JP', 'region' => $region, 'metric' => 'application_users', 'value' => $value, 'quality' => [], 'revision' => (string) Uuid::v7(), 'updated_at' => '2020-01-03T00:00:00Z']);
        }
        $period = new ReportingPeriod($day, $day, AnalyzeAppMetrics::now());
        $query = $this->app()->make(GetAppRegionalDailySummaryReport::class);
        $report = $query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN)))->toArray();
        self::assertSame('80.000000000', $report['applicationUsers']['total']);
        self::assertSame([null, 'JP-13', 'JP-27'], array_column($report['applicationUsers']['regions'], 'region'));
        self::assertSame(['0.000000000', '60.000000000', '40.000000000'], array_column($report['applicationUsers']['regions'], 'value'));
        $unknown = $query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN, '')))->toArray();
        self::assertSame('0.000000000', $unknown['applicationUsers']['total']);
        self::assertSame('measured', $unknown['applicationUsers']['days'][0]['availability']);
        RegionalDailySummary::query()->where('region', 'JP-13')->update(['revision' => (string) Uuid::v7()]);
        $updated = $query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN)))->toArray();
        self::assertNotSame($report['applicationUsers']['revision'], $updated['applicationUsers']['revision']);
        self::assertSame($report['applicationUsers']['total'], $updated['applicationUsers']['total']);
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $legacy = $this->app()->make(GetAppDailySummaryReport::class)->process(new GetAppDailySummaryReportInput($principal, $period, 'JP'))->toArray();
            self::assertArrayNotHasKey('regions', $legacy['applicationUsers']);
            self::assertStringNotContainsString('analyze_regional_daily_summaries', json_encode(DB::getQueryLog(), JSON_THROW_ON_ERROR));
        } finally {
            DB::disableQueryLog();
        }
    }

    public function testMissingPrincipalCannotReadRegionalRows(): void
    {
        $input = new GetAppRegionalDailySummaryReportInput(new PrincipalIdentifier((string) Uuid::v7()), new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeAppMetrics::now()), new RegionalSelection(CountryCode::JAPAN));
        DB::enableQueryLog();

        try {
            $this->app()->make(GetAppRegionalDailySummaryReport::class)->process($input);
            self::fail('A missing Principal must be rejected.');
        } catch (UnauthorizedException) {
            self::assertStringNotContainsString('analyze_regional_daily_summaries', json_encode(DB::getQueryLog(), JSON_THROW_ON_ERROR));
        } finally {
            DB::disableQueryLog();
        }
    }

    public function testUncollectedRegionIsNotMeasuredZeroAndCountryTotalsRemainIndependent(): void
    {
        $principal = CreateAnalyzePrincipal::create()->principalIdentifier();
        $day = new UtcDate('2020-01-01');
        $summary = (new AppDailySummaryFactory(new UuidGenerator()))->create(...AnalyzeAppMetrics::summaryValues($day));
        (new AppDailySummaryRepository())->save($summary);
        AppCountryDailySummary::query()->where('day', (string) $day)->delete();
        AppCountryDailySummary::query()->create(['id' => (string) Uuid::v7(), 'day' => (string) $day, 'country' => 'JP', 'application_users' => '80', 'registered_users' => '30']);
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeAppMetrics::now());
        $query = $this->app()->make(GetAppRegionalDailySummaryReport::class);
        $all = $query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN)))->toArray()['applicationUsers'];
        self::assertSame('80.000000000', $all['total']);
        self::assertSame([], $all['regions']);
        $missing = $query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN, 'JP-13')))->toArray()['applicationUsers'];
        self::assertNull($missing['total']);
        self::assertSame('not_collected', $missing['days'][0]['availability']);
        $key = ['context' => 'app', 'day' => '2020-01-01', 'country' => 'JP', 'region' => 'JP-13', 'wiki_identifier' => '', 'language' => '', 'metric' => 'application_users'];
        DB::table('analyze_regional_daily_summaries')->insert(['context' => 'app', 'day' => '2020-01-01', 'country' => 'JP', 'region' => 'JP-13', 'metric' => 'application_users', 'source' => 'ga4', 'definition_version' => '2', 'value' => '9', 'quality' => '[]', 'revision' => (string) Uuid::v7(), 'updated_at' => '2020-01-03T00:00:00Z']);
        self::assertNull($query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN, 'JP-13')))->toArray()['applicationUsers']['total']);
        DB::table('analyze_regional_daily_summaries')->where($key)->update(['definition_version' => '1', 'source' => 'identity_history']);
        self::assertNull($query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN, 'JP-13')))->toArray()['applicationUsers']['total']);
        DB::table('analyze_regional_daily_summaries')->where($key)->update(['source' => 'ga4']);
        self::assertSame('9.000000000', $query->process(new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN, 'JP-13')))->toArray()['applicationUsers']['total']);
    }

    public function testPreviousPeriodUsesSelectedRegionRatherThanCountryValues(): void
    {
        $principal = CreateAnalyzePrincipal::create()->principalIdentifier();
        foreach (['2019-12-31' => '20', '2020-01-01' => '30'] as $day => $value) {
            foreach (['JP-13' => $value, 'JP-27' => '1000'] as $region => $count) {
                RegionalDailySummary::query()->create(['context' => 'app', 'day' => $day, 'country' => 'JP', 'region' => $region, 'metric' => 'application_users', 'value' => $count, 'quality' => [], 'revision' => (string) Uuid::v7(), 'updated_at' => '2020-01-03T00:00:00Z']);
            }
        }
        $period = new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-01'), AnalyzeAppMetrics::now());
        $input = new GetAppRegionalDailySummaryReportInput($principal, $period, new RegionalSelection(CountryCode::JAPAN, 'JP-13'));
        $data = $this->app()->make(GetAppRegionalDailySummaryReport::class)->process($input)->toArray();
        self::assertNotNull($data['applicationUsers']['comparison']);
        self::assertSame('50.0', $data['applicationUsers']['comparison']['percentageChange']);
        self::assertSame('2019-12-31', $data['applicationUsers']['comparison']['from']);
    }
}
