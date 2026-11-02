<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Client;

use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use Source\Analyze\Shared\Infrastructure\Client\MockGoogleAnalyticsClient;
use Tests\TestCase;

class MockGoogleAnalyticsClientTest extends TestCase
{
    public function testFixedReportsSupportPagination(): void
    {
        $client = new MockGoogleAnalyticsClient();
        self::assertSame('250', $client->runReport(new RunReportRequest('2020-01-01', ['totalUsers'], ['date']))->rows()[0]->metric('totalUsers'));
        $first = $client->runReport(new RunReportRequest('2020-01-01', ['eventCount'], ['date', 'customEvent:wiki_id', 'customEvent:wiki_translation_set_id', 'customEvent:wiki_language', 'countryId'], offset: 0, limit: 1));
        $second = $client->runReport(new RunReportRequest('2020-01-01', ['eventCount'], ['date', 'customEvent:wiki_id', 'customEvent:wiki_translation_set_id', 'customEvent:wiki_language', 'countryId'], offset: 1, limit: 1));
        self::assertSame(2, $first->rowCount());
        self::assertSame('JP', $first->rows()[0]->dimension('countryId'));
        self::assertSame('US', $second->rows()[0]->dimension('countryId'));
        self::assertSame('40', $second->rows()[0]->metric('eventCount'));
    }
}
