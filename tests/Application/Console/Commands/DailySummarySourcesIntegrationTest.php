<?php

declare(strict_types=1);

namespace Tests\Application\Console\Commands;

use Application\Console\Commands\ImportAppDailySummaryCommand;
use Application\Console\Commands\ImportWikiDailySummaryCommand;
use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClient;
use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClientInterface;
use Application\Models\Analyze\App\AppCountryDailySummary;
use Application\Models\Analyze\App\AppDailySummary;
use Application\Models\Analyze\Wiki\WikiDailySummary;
use Application\Models\Identity\Identity;
use Illuminate\Support\Facades\Artisan;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\Service\RegisteredIdentityCountQueryServiceInterface;
use Source\Analyze\App\Infrastructure\Service\RegisteredIdentityCountQueryService;
use Source\Analyze\Shared\Infrastructure\Client\MockGoogleAnalyticsClient;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationsQueryServiceInterface;
use Source\Analyze\Wiki\Infrastructure\Service\DailyWikiOperationsQueryService;
use Tests\Helper\AnalyticsHttpStub;
use Tests\Helper\AnalyzeSourceRecords;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class DailySummarySourcesIntegrationTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://example.test']);
    }

    #[DataProvider('environments')]
    public function testOnlyTheAnalyticsClientChangesByEnvironment(string $environment, bool $mock): void
    {
        $this->app()->instance('env', $environment);
        config([
            'analyze.ga4_property_id' => '123456',
            'google.credentials' => json_encode([
                'type' => 'authorized_user',
                'client_id' => 'test-client',
                'client_secret' => 'test-secret',
                'refresh_token' => 'test-token',
            ], JSON_THROW_ON_ERROR),
        ]);
        self::assertInstanceOf($mock ? MockGoogleAnalyticsClient::class : GoogleAnalyticsClient::class, $this->app()->make(GoogleAnalyticsClientInterface::class));
        self::assertInstanceOf(RegisteredIdentityCountQueryService::class, $this->app()->make(RegisteredIdentityCountQueryServiceInterface::class));
        self::assertInstanceOf(DailyWikiOperationsQueryService::class, $this->app()->make(DailyWikiOperationsQueryServiceInterface::class));
    }

    /** @return array<string, array{string, bool}> */
    public static function environments(): array
    {
        return [
            'local' => ['local', true],
            'testing' => ['testing', true],
            'staging' => ['staging', false],
            'production' => ['production', false],
        ];
    }

    public function testLocalDiImportsMockAnalyticsAndDatabaseHistoryWithStableEntityIdentifiers(): void
    {
        self::assertInstanceOf(MockGoogleAnalyticsClient::class, $this->app()->make(GoogleAnalyticsClientInterface::class));
        $this->commands();
        $this->runBoth();
        $app = AppDailySummary::query()->firstOrFail();
        self::assertSame('250.000000000', $app->application_users);
        self::assertSame('0.000000000', (string) AppCountryDailySummary::query()->sum('registered_users'));
        self::assertSame(2, WikiDailySummary::query()->count());
        $jp = WikiDailySummary::query()->where('country', 'JP')->firstOrFail();
        $us = WikiDailySummary::query()->where('country', 'US')->firstOrFail();
        self::assertSame('120.000000000', $jp->views);
        self::assertSame('60.000000000', $jp->viewers);
        self::assertSame('1800.250000001', $jp->engagement_seconds);
        self::assertSame('0.000000000', $jp->submissions);
        self::assertSame('0.000000000', $jp->publications);
        self::assertSame('0.000000000', $us->submissions);
        self::assertSame('identity_history', $app->measurements['registered_users']['source']);
        self::assertSame('wiki_history', $jp->measurements['wiki_submissions']['source']);
        $identifiers = WikiDailySummary::query()->orderBy('id')->pluck('id')->all();
        $identity = new Identity();
        $identity->forceFill(['id' => StrTestHelper::generateUuid(), 'identity_name' => 'test', 'email' => 'test@example.test', 'language' => 'ja', 'created_at' => '2019-01-01', 'updated_at' => '2019-01-01'])->save();
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01 01:00:00');
        AnalyzeSourceRecords::history('publish', '2020-01-01 02:00:00');
        $this->runBoth();
        self::assertSame('1.000000000', (string) AppCountryDailySummary::query()->sum('registered_users'));
        $updatedJp = WikiDailySummary::query()->where('country', 'JP')->firstOrFail();
        self::assertSame('1.000000000', $updatedJp->submissions);
        self::assertSame('1.000000000', $updatedJp->publications);
        self::assertSame($app->id, AppDailySummary::query()->firstOrFail()->id);
        self::assertSame($identifiers, WikiDailySummary::query()->orderBy('id')->pluck('id')->all());
    }

    public function testLiveSourcesRunActualHttpClientAndOwningQueriesThroughBothCommands(): void
    {
        $this->app()->instance('env', 'production');
        $http = new AnalyticsHttpStub();
        $this->app()->instance(GoogleAnalyticsClientInterface::class, $http->client());
        $identity = new Identity();
        $identity->forceFill(['id' => StrTestHelper::generateUuid(), 'identity_name' => 'test', 'email' => 'test@example.test', 'language' => 'ja', 'created_at' => '2019-01-01', 'updated_at' => '2019-01-01'])->save();
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01 01:00:00');
        AnalyzeSourceRecords::history('publish', '2020-01-01 02:00:00', 'US');
        $this->commands();
        $this->runBoth();
        self::assertSame('250.000000000', AppDailySummary::query()->firstOrFail()->application_users);
        self::assertSame('1.000000000', (string) AppCountryDailySummary::query()->sum('registered_users'));
        self::assertSame(2, WikiDailySummary::query()->count());
        self::assertSame('120.000000000', WikiDailySummary::query()->where('country', 'JP')->firstOrFail()->views);
        self::assertSame('0.000000000', WikiDailySummary::query()->where('country', 'US')->firstOrFail()->views);
        self::assertSame('1.000000000', WikiDailySummary::query()->where('country', 'US')->firstOrFail()->publications);
        self::assertCount(9, $http->requests);
        $before = WikiDailySummary::query()->orderBy('id')->get()->toArray();
        $http->status = 429;
        self::assertSame(1, Artisan::call('analyze:import-wiki-daily-summary', ['--from' => '2020-01-01','--to' => '2020-01-01']));
        self::assertSame($before, WikiDailySummary::query()->orderBy('id')->get()->toArray());
        $http->status = 200;
        $http->metadata = ['timeZone' => 'UTC', 'subjectToThresholding' => true];
        self::assertSame(1, Artisan::call('analyze:import-wiki-daily-summary', ['--from' => '2020-01-01', '--to' => '2020-01-01']));
        self::assertSame($before, WikiDailySummary::query()->orderBy('id')->get()->toArray());
        $http->metadata = ['timeZone' => 'Asia/Tokyo'];
        self::assertSame(1, Artisan::call('analyze:import-wiki-daily-summary', ['--from' => '2020-01-01', '--to' => '2020-01-01']));
        self::assertSame($before, WikiDailySummary::query()->orderBy('id')->get()->toArray());
    }

    private function commands(): void
    {
        config(['analyze.reimport_days' => 3, 'analyze.max_pages_per_day' => 1000]);
        Artisan::registerCommand(new ImportAppDailySummaryCommand());
        Artisan::registerCommand(new ImportWikiDailySummaryCommand());
    }

    private function runBoth(): void
    {
        foreach (['app','wiki'] as $domain) {
            $code = Artisan::call('analyze:import-'.$domain.'-daily-summary', ['--from' => '2020-01-01','--to' => '2020-01-01']);
            self::assertSame(0, $code, Artisan::output());
        }
    }
}
