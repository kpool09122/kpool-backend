<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Infrastructure\Service;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Analyze\App\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\App\Application\Service\RegisteredIdentityCountQueryServiceInterface;
use Source\Analyze\App\Infrastructure\Service\DailyMetricsFetchService;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyticsHttpStub;
use Tests\Helper\AnalyzeAppMetrics;
use Tests\TestCase;

class DailyMetricsFetchServiceTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://example.test']);
    }

    public function testFetchesGlobalUsersAndClosingBalanceThroughConstructorDependencies(): void
    {
        config(['app.frontend_url' => 'https://public.example.test:8443/wiki/test?lang=ja']);
        $http = new AnalyticsHttpStub();
        $day = new UtcDate('2020-01-01');
        /** @var RegisteredIdentityCountQueryServiceInterface&MockInterface $query */
        $query = Mockery::mock(RegisteredIdentityCountQueryServiceInterface::class);
        $query->shouldReceive('countByCountryBefore')->once()->withArgs(static fn (DateTimeImmutable $end): bool => $end == $day->next()->instant())->andReturn(['JP' => '1200', '' => '34']);
        $data = (new DailyMetricsFetchService($http->client(), $query))->fetchSummary($day, AnalyzeAppMetrics::now(), 1);
        self::assertSame('250', $data->applicationUsers()->count());
        self::assertSame('1234', $data->registeredUsers()->count());
        self::assertEquals($day->next()->instant(), $data->registeredUsers()->asOf());
        self::assertSame('ga4', $data->applicationUsers()->provenance()->definition()->source);
        self::assertSame('JP', $data->countries()[0]->countryKey());
        self::assertSame('60', $data->countries()[0]->applicationUsers->count());
        self::assertSame('1200', $data->countries()[0]->registeredUsers->count());
        self::assertSame('', $data->countries()[1]->countryKey());
        self::assertSame('34', $data->countries()[1]->registeredUsers->count());
        $body = (string) $http->requests[0]->getBody();
        self::assertStringContainsString('totalUsers', $body);
        self::assertStringContainsString('public.example.test', $body);
        self::assertStringContainsString('EXACT', $body);
        self::assertStringNotContainsString('8443', $body);
        self::assertStringNotContainsString('lang=ja', $body);
        self::assertStringNotContainsString('pagePath', $body);
        self::assertStringNotContainsString('wiki_id', $body);
    }

    /** @param array<string, string> $countries */
    #[DataProvider('registrationCounts')]
    public function testRegistrationTotalIsDerivedFromCountriesWithoutLosingIntegerPrecision(array $countries, string $expected): void
    {
        $http = new AnalyticsHttpStub();
        /** @var RegisteredIdentityCountQueryServiceInterface&MockInterface $query */
        $query = Mockery::mock(RegisteredIdentityCountQueryServiceInterface::class);
        $query->shouldReceive('countByCountryBefore')->once()->andReturn($countries);
        $data = (new DailyMetricsFetchService($http->client(), $query))->fetchSummary(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), 1);
        self::assertSame($expected, $data->registeredUsers()->count());
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function registrationCounts(): array
    {
        return [
            'unknown registrations included' => [['JP' => '12', 'US' => '7', '' => '4'], '23'],
            'zero registrations' => [['' => '0'], '0'],
            'empty result' => [[], '0'],
            'twenty digit count' => [['JP' => '99999999999999999990', '' => '9'], '99999999999999999999'],
        ];
    }

    public function testCompleteEmptyApiReportMeansZeroUsers(): void
    {
        $http = new AnalyticsHttpStub();
        $http->empty = true;
        $data = $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), 1);
        self::assertSame('0', $data->applicationUsers()->count());
        self::assertSame('1234', $data->registeredUsers()->count());
    }

    public function testNonUtcDataCannotProduceASummary(): void
    {
        $http = new AnalyticsHttpStub();
        $http->metadata = ['timeZone' => 'Asia/Tokyo'];
        $this->expectException(IncompleteDailyMetricsException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), 1);
    }

    public function testApiFailureIsNotConvertedToZero(): void
    {
        $http = new AnalyticsHttpStub();
        $http->status = 503;
        $this->expectException(MetricsFetchFailedException::class);
        $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), 1);
    }

    public function testHistoryQueryFailureIsNotConvertedToZero(): void
    {
        $http = new AnalyticsHttpStub();
        /** @var RegisteredIdentityCountQueryServiceInterface&MockInterface $query */
        $query = Mockery::mock(RegisteredIdentityCountQueryServiceInterface::class);
        $query->shouldReceive('countByCountryBefore')->once()->andThrow(new RuntimeException('History query failed.'));
        $this->expectException(MetricsFetchFailedException::class);
        (new DailyMetricsFetchService($http->client(), $query))->fetchSummary(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), 1);
    }

    public function testHistoricalDayNeedsNoStartDateConfiguration(): void
    {
        $this->app()->instance('env', 'production');
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $data = $this->service($http)->fetchSummary(new UtcDate('2009-12-31'), AnalyzeAppMetrics::now(), 1);
        self::assertSame('250', $data->applicationUsers()->count());
        self::assertSame('1234', $data->registeredUsers()->count());
        self::assertCount(2, $http->requests);
    }

    public function testInvalidFrontendUrlDoesNotCallTheApi(): void
    {
        config(['app.frontend_url' => 'not-a-url']);
        $http = new AnalyticsHttpStub();

        try {
            $this->service($http)->fetchSummary(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), 1);
            self::fail('An invalid frontend URL must be rejected.');
        } catch (MetricsFetchFailedException) {
            self::assertSame([], $http->requests);
        }
    }

    private function service(AnalyticsHttpStub $http): DailyMetricsFetchService
    {
        /** @var RegisteredIdentityCountQueryServiceInterface&MockInterface $query */
        $query = Mockery::mock(RegisteredIdentityCountQueryServiceInterface::class);
        $query->shouldReceive('countByCountryBefore')->andReturn(['JP' => '1200', '' => '34']);

        return new DailyMetricsFetchService($http->client(), $query);
    }
}
