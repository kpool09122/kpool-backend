<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Application\UseCase;

use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClientInterface;
use Application\Models\Analyze\App\AppDailySummary as AppSummaryModel;
use Application\Models\Analyze\RegionalDailySummary;
use Application\Models\Analyze\Wiki\WikiDailySummary as WikiSummaryModel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Analyze\App\Application\Service\AppDailySummaryData;
use Source\Analyze\App\Application\Service\DailyMetricsFetchServiceInterface as AppDailyMetricsFetchServiceInterface;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummary;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryInput;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryOutput;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface as WikiDailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummary;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInput;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryOutput;
use Source\Analyze\Wiki\Infrastructure\Factory\WikiDailySummaryFactory;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyticsHttpStub;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\TestCase;

#[Group('useDb')]
class RegionalDailySummaryImportTest extends TestCase
{
    #[DataProvider('contexts')]
    public function testRegionalAcquisitionDoesNotPublishMeasurements(string $context, int $count): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $this->app()->instance(GoogleAnalyticsClientInterface::class, $http->client());
        $service = $this->app()->make($context === 'app' ? AppDailyMetricsFetchServiceInterface::class : WikiDailyMetricsFetchServiceInterface::class);
        $data = $service->fetchSummary(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), 10);
        if ($data instanceof AppDailySummaryData) {
            $observations = $data->regions();
        } else {
            $target = $data->remaining()[0];
            $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create($target->wikiIdentifier(), $target->translationSetIdentifier(), $target->language(), $target->country(), $target->views(), $target->viewers(), $target->engagementTime(), $target->submissions(), $target->publications());
            $observations = $data->regionsFor($summary);
        }
        self::assertCount($count, $observations);
        self::assertSame('JP-13', $observations[0]->region);
        self::assertSame(0, RegionalDailySummary::query()->count());
        self::assertSame(0, AppSummaryModel::query()->count());
        self::assertSame(0, WikiSummaryModel::query()->count());
    }

    #[DataProvider('contexts')]
    public function testRegionalPersistenceFailureRollsBackAlreadyWrittenGlobalAndRegionalValues(string $context, int $count): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $this->app()->instance(GoogleAnalyticsClientInterface::class, $http->client());
        $day = new UtcDate('2020-01-01');
        $now = AnalyzeAppMetrics::now();
        $usecaseClass = $context === 'app' ? ImportAppDailySummary::class : ImportWikiDailySummary::class;
        $input = $context === 'app' ? new ImportAppDailySummaryInput($day, $now) : new ImportWikiDailySummaryInput($day, $now);
        $output = $context === 'app' ? new ImportAppDailySummaryOutput() : new ImportWikiDailySummaryOutput();
        DB::transaction(fn () => $this->app()->make($usecaseClass)->process($input, $output));
        $model = $context === 'app' ? AppSummaryModel::class : WikiSummaryModel::class;
        $metric = $context === 'app' ? 'application_users' : 'views';
        $before = $model::query()->orderBy('id')->get()->toArray();
        $regionalBefore = RegionalDailySummary::query()->orderBy('context')->orderBy('day')->orderBy('country')->orderBy('region')->orderBy('wiki_identifier')->orderBy('language')->orderBy('metric')->get()->toArray();
        $http->metricOverrides = ['totalUsers' => '7', 'eventCount' => '7'];
        $http->dimensionOverrides = ['region' => 'Osaka'];
        $written = false;
        DB::listen(function (QueryExecuted $query) use ($model, $metric, &$written): void {
            if (! str_starts_with($query->sql, 'insert into "analyze_regional_daily_summaries"')) {
                return;
            }
            self::assertSame('7.000000000', $model::query()->firstOrFail()->getAttribute($metric));
            self::assertSame(['JP-27'], RegionalDailySummary::query()->distinct()->pluck('region')->all());
            $written = true;

            throw new RuntimeException('Regional persistence failed.');
        });

        try {
            DB::transaction(fn () => $this->app()->make($usecaseClass)->process($input, $output));
            self::fail('Persistence failure must escape the use case.');
        } catch (RuntimeException $exception) {
            self::assertSame('Regional persistence failed.', $exception->getMessage());
        }
        self::assertTrue($written);
        self::assertSame($before, $model::query()->orderBy('id')->get()->toArray());
        self::assertSame($regionalBefore, RegionalDailySummary::query()->orderBy('context')->orderBy('day')->orderBy('country')->orderBy('region')->orderBy('wiki_identifier')->orderBy('language')->orderBy('metric')->get()->toArray());
    }

    /** @return iterable<string, array{string, int}> */
    public static function contexts(): iterable
    {
        yield 'App' => ['app', 2];
        yield 'Wiki' => ['wiki', 3];
    }

    #[DataProvider('contexts')]
    public function testExistingUseCaseImportsAndReplacesRegionsWithoutACommand(string $context, int $count): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $this->app()->instance(GoogleAnalyticsClientInterface::class, $http->client());
        $day = new UtcDate('2020-01-01');
        $now = AnalyzeAppMetrics::now();
        $usecase = $this->app()->make($context === 'app' ? ImportAppDailySummary::class : ImportWikiDailySummary::class);
        $input = $context === 'app' ? new ImportAppDailySummaryInput($day, $now) : new ImportWikiDailySummaryInput($day, $now);
        $output = $context === 'app' ? new ImportAppDailySummaryOutput() : new ImportWikiDailySummaryOutput();
        DB::transaction(fn () => $usecase->process($input, $output));
        self::assertSame($count, RegionalDailySummary::query()->where('context', $context)->count());
        self::assertSame(['JP-13'], RegionalDailySummary::query()->distinct()->pluck('region')->all());

        $http->dimensionOverrides = ['region' => 'Osaka'];
        DB::transaction(fn () => $usecase->process($input, $output));
        self::assertSame($count, RegionalDailySummary::query()->where('context', $context)->count());
        self::assertSame(['JP-27'], RegionalDailySummary::query()->distinct()->pluck('region')->all());
    }
}
