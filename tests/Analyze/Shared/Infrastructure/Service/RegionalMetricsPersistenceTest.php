<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Service;

use Application\Models\Analyze\RegionalDailySummary;
use Application\Models\Identity\ArchivedIdentity;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\App\Infrastructure\Repository\AppDailySummaryRepository;
use Source\Analyze\App\Infrastructure\Service\DailyMetricsFetchService;
use Source\Analyze\App\Infrastructure\Service\RegisteredIdentityCountQueryService;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Service\RegionalMetricsFetchService;
use Source\Analyze\Shared\Infrastructure\Service\RegionNormalizer;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Symfony\Component\Uid\Uuid;
use Tests\Helper\AnalyticsHttpStub;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\TestCase;

#[Group('useDb')]
class RegionalMetricsPersistenceTest extends TestCase
{
    private function import(DailyMetricsFetchService $service, UtcDate $day, DateTimeImmutable $now, int $maxPages): void
    {
        $data = $service->fetchSummary($day, $now, $maxPages);
        $repository = new AppDailySummaryRepository();
        $summary = $repository->findByDay($day) ?? (new AppDailySummaryFactory(new UuidGenerator()))->create($data->applicationUsers(), $data->registeredUsers(), $data->countries());
        $summary->replaceAnalysis($data->applicationUsers(), $data->registeredUsers(), $data->countries(), $data->regions());
        $repository->save($summary);
    }

    public function testPaginationAndPageBoundAreEnforced(): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $http->paginateRegions = true;
        $service = new DailyMetricsFetchService($http->client(), new RegisteredIdentityCountQueryService(), new RegionalMetricsFetchService($http->client(), new RegionNormalizer()));
        $day = new UtcDate('2020-01-01');
        DB::transaction(fn () => $this->import($service, $day, AnalyzeAppMetrics::now(), 2));
        self::assertSame(['JP', 'US'], RegionalDailySummary::query()->where('metric', 'application_users')->orderBy('country')->pluck('country')->all());
        self::assertCount(4, $http->requests);
        $before = RegionalDailySummary::query()->orderBy('context')->orderBy('day')->orderBy('country')->orderBy('region')->orderBy('wiki_identifier')->orderBy('language')->orderBy('metric')->get()->toArray();

        try {
            DB::transaction(fn () => $this->import($service, $day, AnalyzeAppMetrics::now(), 1));
            self::fail('Truncation must fail.');
        } catch (MetricsFetchFailedException) {
            self::assertSame($before, RegionalDailySummary::query()->orderBy('context')->orderBy('day')->orderBy('country')->orderBy('region')->orderBy('wiki_identifier')->orderBy('language')->orderBy('metric')->get()->toArray());
        }
    }

    public function testUnknownAndUnmappedNamesAreStoredWithDifferentQualityAndKnownZeros(): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $http->metricOverrides = ['totalUsers' => '0'];
        $service = new DailyMetricsFetchService($http->client(), new RegisteredIdentityCountQueryService(), new RegionalMetricsFetchService($http->client(), new RegionNormalizer()));
        $day = new UtcDate('2020-01-01');
        foreach (['(not set)' => 'region_unknown', 'new geographic label' => 'region_unmapped'] as $name => $quality) {
            $http->dimensionOverrides = ['region' => $name];
            DB::transaction(fn () => $this->import($service, $day, AnalyzeAppMetrics::now(), 2));
            $row = RegionalDailySummary::query()->where('metric', 'application_users')->firstOrFail();
            self::assertSame('', $row->region);
            self::assertSame([$quality], $row->quality);
            self::assertSame('0.000000000', $row->value);
        }
    }

    public function testRegistrationRegionClosingBalanceIncludesArchivesOnlyBeforeWithdrawal(): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        ArchivedIdentity::query()->create(['id' => (string) Uuid::v7(), 'identity_id' => (string) Uuid::v7(), 'language' => 'ja', 'identity_created_at' => '2019-12-31T00:00:00Z', 'archived_at' => '2020-01-02T12:00:00Z', 'registration_country' => 'DE', 'registration_region' => 'DE-BE']);
        $http = new AnalyticsHttpStub();
        $http->dimensionOverrides = ['countryId' => 'DE', 'region' => 'Berlin'];
        $service = new DailyMetricsFetchService($http->client(), new RegisteredIdentityCountQueryService(), new RegionalMetricsFetchService($http->client(), new RegionNormalizer()));
        $day = new UtcDate('2020-01-01');
        DB::transaction(fn () => $this->import($service, $day, AnalyzeAppMetrics::now(), 2));
        self::assertSame('1.000000000', RegionalDailySummary::query()->where('metric', 'registered_users')->firstOrFail()->value);
        DB::transaction(fn () => $this->import($service, $day->next(), AnalyzeAppMetrics::now(), 2));
        self::assertSame('0.000000000', RegionalDailySummary::query()->where('metric', 'registered_users')->where('day', '2020-01-02')->firstOrFail()->value);
    }

    public function testImportsValidRegionOutsideMapCoverageAndPreservesPublishedValuesOnFailure(): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $http->dimensionOverrides = ['countryId' => 'DE', 'region' => 'Berlin'];
        $service = new DailyMetricsFetchService($http->client(), new RegisteredIdentityCountQueryService(), new RegionalMetricsFetchService($http->client(), new RegionNormalizer()));
        $day = new UtcDate('2020-01-01');
        DB::transaction(fn () => $this->import($service, $day, AnalyzeAppMetrics::now(), 2));
        $row = RegionalDailySummary::query()->where('metric', 'application_users')->firstOrFail();
        self::assertSame('DE-BE', $row->region);
        self::assertSame('60.000000000', $row->value);
        $revision = $row->revision;
        $http->status = 503;

        try {
            DB::transaction(fn () => $this->import($service, $day, AnalyzeAppMetrics::now(), 2));
            self::fail('Fetch failure must fail the day.');
        } catch (MetricsFetchFailedException) {
            self::assertSame($revision, RegionalDailySummary::query()->where('context', 'app')->where('day', (string) $day)->where('metric', 'application_users')->firstOrFail()->revision);
        }
    }
}
