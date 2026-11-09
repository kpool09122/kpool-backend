<?php

declare(strict_types=1);

namespace Tests\Application\Console\Commands;

use Application\Console\Commands\ImportAppDailySummaryCommand;
use Application\Console\Commands\ImportWikiDailySummaryCommand;
use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClientInterface;
use Application\Models\Analyze\RegionalDailySummary;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Exceptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Helper\AnalyticsHttpStub;
use Tests\TestCase;

#[Group('useDb')]
class RegionalDailySummaryImportCommandTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function contexts(): iterable
    {
        yield 'App' => ['app'];
        yield 'Wiki' => ['wiki'];
    }

    #[DataProvider('contexts')]
    public function testRealImportRollsBackFailedRegionThenContinuesLaterDaysAndManualRetryRecovers(string $context): void
    {
        config(['app.frontend_url' => 'https://example.test', 'analyze.max_pages_per_day' => 10, 'analyze.reimport_days' => 3]);
        $this->app()->instance('env', 'production');
        $this->app()->make('Illuminate\Contracts\Console\Kernel')->registerCommand($this->app()->make($context === 'app' ? ImportAppDailySummaryCommand::class : ImportWikiDailySummaryCommand::class));
        $http = new AnalyticsHttpStub();
        $this->app()->instance(GoogleAnalyticsClientInterface::class, $http->client());
        Exceptions::fake();
        $command = 'analyze:import-' . $context . '-daily-summary';
        self::assertSame(0, Artisan::call($command, ['--from' => '2020-01-01', '--to' => '2020-01-01']), Artisan::output());
        $before = RegionalDailySummary::query()->where('context', $context)->where('day', '2020-01-01')->orderBy('context')->orderBy('day')->orderBy('country')->orderBy('region')->orderBy('wiki_identifier')->orderBy('language')->orderBy('metric')->get()->toArray();
        $http->regionalFailureDays = ['2020-01-01'];
        self::assertSame(1, Artisan::call($command, ['--from' => '2020-01-01', '--to' => '2020-01-02']));
        Exceptions::assertReportedCount(1);
        self::assertSame($before, RegionalDailySummary::query()->where('context', $context)->where('day', '2020-01-01')->orderBy('context')->orderBy('day')->orderBy('country')->orderBy('region')->orderBy('wiki_identifier')->orderBy('language')->orderBy('metric')->get()->toArray());
        self::assertTrue(RegionalDailySummary::query()->where('context', $context)->where('day', '2020-01-02')->exists());
        $http->regionalFailureDays = [];
        self::assertSame(0, Artisan::call($command, ['--from' => '2020-01-01', '--to' => '2020-01-01']), Artisan::output());
        self::assertNotSame($before, RegionalDailySummary::query()->where('context', $context)->where('day', '2020-01-01')->orderBy('context')->orderBy('day')->orderBy('country')->orderBy('region')->orderBy('wiki_identifier')->orderBy('language')->orderBy('metric')->get()->toArray());
        self::assertSame(['JP-13'], RegionalDailySummary::query()->where('context', $context)->distinct()->pluck('region')->all());
    }
}
