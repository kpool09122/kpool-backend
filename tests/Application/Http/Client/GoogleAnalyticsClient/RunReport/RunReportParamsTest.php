<?php

declare(strict_types=1);

namespace Tests\Application\Http\Client\GoogleAnalyticsClient\RunReport;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportParams;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use Tests\TestCase;

class RunReportParamsTest extends TestCase
{
    public function testColumnsAreMappedByTheirRequestedGa4NamesAndPreservePrecision(): void
    {
        $data = ['dimensionHeaders' => [['name' => 'countryId'], ['name' => 'date']],
            'metricHeaders' => [['name' => 'userEngagementDuration'], ['name' => 'totalUsers']], 'metadata' => ['timeZone' => 'UTC'], 'rowCount' => 1,
            'rows' => [['dimensionValues' => [['value' => '(not set)'], ['value' => '20200101']], 'metricValues' => [['value' => '1800.250000001'], ['value' => '60']]]]];
        $params = RunReportParams::fromArray($data, new RunReportRequest('2020-01-01', ['userEngagementDuration', 'totalUsers'], ['countryId', 'date']));
        self::assertSame('(not set)', $params->rows()[0]->dimension('countryId'));
        self::assertSame('1800.250000001', $params->rows()[0]->metric('userEngagementDuration'));
        self::assertSame('60', $params->rows()[0]->metric('totalUsers'));
    }

    public function testCompleteEmptyReportAcceptsOmittedProtoDefaultRowCount(): void
    {
        $params = RunReportParams::fromArray(['dimensionHeaders' => [['name' => 'date']], 'metricHeaders' => [['name' => 'totalUsers']], 'metadata' => ['timeZone' => 'UTC']], new RunReportRequest('2020-01-01', ['totalUsers'], ['date']));
        self::assertSame(0, $params->rowCount());
        self::assertSame([], $params->rows());
    }

    public function testAggregateReportCanOmitDimensionHeadersAndDimensionValues(): void
    {
        $params = RunReportParams::fromArray(['metricHeaders' => [['name' => 'sessions']], 'metadata' => ['timeZone' => 'UTC'], 'rowCount' => 1,
            'rows' => [['metricValues' => [['value' => '17']]]]], new RunReportRequest('2020-01-01', ['sessions']));
        self::assertSame([], $params->rows()[0]->dimensions());
        self::assertSame('17', $params->rows()[0]->metric('sessions'));
    }

    public function testReportCannotExposeRowsWithHeadersFromAnotherMetric(): void
    {
        $this->expectException(GoogleAnalyticsRequestFailedException::class);
        RunReportParams::fromArray(['dimensionHeaders' => [['name' => 'date']], 'metricHeaders' => [['name' => 'eventCount']], 'metadata' => ['timeZone' => 'UTC'], 'rowCount' => 0], new RunReportRequest('2020-01-01', ['totalUsers'], ['date']));
    }
}
