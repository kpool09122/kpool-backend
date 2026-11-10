<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Service;

use RuntimeException;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Service\RegionalMetricsFetchService;
use Source\Analyze\Shared\Infrastructure\Service\RegionNormalizer;
use Tests\Helper\AnalyticsHttpStub;
use Tests\TestCase;

class RegionalMetricsFetchServiceTest extends TestCase
{
    public function testFetchesWikiEngagementWithSamplesAndWikiAttribution(): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $http->metadata = ['timeZone' => 'Atlantic/Reykjavik'];
        $observations = (new RegionalMetricsFetchService($http->client(), new RegionNormalizer()))->fetch('wiki', 'engagement_seconds', 'userEngagementDuration', new UtcDate('2020-01-01'), 2);
        self::assertCount(1, $observations);
        self::assertSame('JP-13', $observations[0]->region);
        self::assertSame('1800.250000001', $observations[0]->value);
        self::assertSame(60, $observations[0]->denominator);
        self::assertSame('69200000-0000-7000-8000-000000000001', $observations[0]->wikiIdentifier);
        self::assertSame('ja', $observations[0]->language);
        self::assertStringContainsString('hostName', (string) $http->requests[0]->getBody());
        self::assertStringContainsString('pagePath', (string) $http->requests[0]->getBody());
    }

    public function testRejectsIncompleteRegionalPagination(): void
    {
        config(['app.frontend_url' => 'https://example.test']);
        $http = new AnalyticsHttpStub();
        $http->paginate = true;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Regional report page limit exceeded.');
        (new RegionalMetricsFetchService($http->client(), new RegionNormalizer()))->fetch('app', 'application_users', 'totalUsers', new UtcDate('2020-01-01'), 1);
    }
}
