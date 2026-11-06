<?php

declare(strict_types=1);

namespace Tests\Application\Http\Client\GoogleAnalyticsClient;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\FilterExpression;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Helper\AnalyticsHttpStub;
use Tests\TestCase;

class GoogleAnalyticsClientTest extends TestCase
{
    public function testCallerSuppliesArbitraryGa4MetricsDimensionsAndFilters(): void
    {
        $http = new AnalyticsHttpStub();
        $client = $http->client();
        $filter = FilterExpression::all(FilterExpression::inList('hostName', ['portal.example.test']), FilterExpression::negate(FilterExpression::exact('countryId', 'US')));
        $response = $client->runReport(new RunReportRequest('2020-01-01', ['sessions', 'screenPageViews'], ['deviceCategory', 'date', 'countryId'], $filter));
        self::assertSame(1, $response->rowCount());
        self::assertSame('17', $response->rows()[0]->metric('sessions'));
        self::assertSame('91', $response->rows()[0]->metric('screenPageViews'));
        self::assertSame('mobile', $response->rows()[0]->dimension('deviceCategory'));
        self::assertSame('20200101', $response->rows()[0]->dimension('date'));
        self::assertCount(1, $http->requests);
        self::assertSame('Bearer test-secret', $http->requests[0]->getHeaderLine('Authorization'));
        $body = (string) $http->requests[0]->getBody();
        self::assertStringContainsString('portal.example.test', $body);
        self::assertStringContainsString('notExpression', $body);
        self::assertStringNotContainsString('wiki_id', $body);
        $client->runReport(new RunReportRequest('2020-01-01', ['sessions'], ['date'], offset: 1, limit: 1));
        self::assertCount(2, $http->requests);
        self::assertStringContainsString('"offset":"1"', (string) $http->requests[1]->getBody());
    }

    public function testGa4TimeZoneAndDimensionValuesAreReturnedWithoutAnalyzeInterpretation(): void
    {
        $http = new AnalyticsHttpStub();
        $http->metadata = ['timeZone' => 'Asia/Tokyo'];
        $http->dimensionOverrides = ['countryId' => '(not set)'];
        $response = $http->client()->runReport(new RunReportRequest('2020-01-01', ['sessions'], ['countryId', 'date']));
        self::assertSame('Asia/Tokyo', $response->params()->timeZone());
        self::assertSame('(not set)', $response->rows()[0]->dimension('countryId'));
    }

    /** @return iterable<string,array{array<string,mixed>}> */
    public static function incompleteMetadata(): iterable
    {
        yield 'thresholding' => [['timeZone' => 'UTC', 'subjectToThresholding' => true]];
        yield 'other row loss' => [['timeZone' => 'UTC', 'dataLossFromOtherRow' => true]];
        yield 'sampled' => [['timeZone' => 'UTC', 'samplingMetadatas' => [['samplesReadCount' => '1', 'samplingSpaceSize' => '2']]]];
        yield 'restricted' => [['timeZone' => 'UTC', 'schemaRestrictionResponse' => ['activeMetricRestrictions' => ['totalUsers']]]];
    }

    /** @param array<string,mixed> $metadata */
    #[DataProvider('incompleteMetadata')]
    public function testIncompleteReportsFailClosed(array $metadata): void
    {
        $http = new AnalyticsHttpStub();
        $http->metadata = $metadata;
        $this->expectException(GoogleAnalyticsRequestFailedException::class);
        $http->client()->runReport(new RunReportRequest('2020-01-01', ['sessions'], ['date']));
    }

    public function testEmptyCompleteReportIsDistinctFromFailure(): void
    {
        $http = new AnalyticsHttpStub();
        $http->empty = true;
        $response = $http->client()->runReport(new RunReportRequest('2020-01-01', ['sessions'], ['date']));
        self::assertSame(0, $response->rowCount());
        self::assertSame([], $response->rows());
    }

    /** @return iterable<string,array{string}> */
    public static function failures(): iterable
    {
        yield 'other row' => ['otherRow'];
        yield 'missing metric' => ['missingMetric'];
        yield 'wrong day' => ['wrongDate'];
        yield 'HTTP failure' => ['status'];
    }

    #[DataProvider('failures')]
    public function testInvalidResponsesAndHttpErrorsAreSanitized(string $failure): void
    {
        $http = new AnalyticsHttpStub();
        match ($failure) {
            'wrongDate' => $http->wrongDate = true, 'otherRow' => $http->otherRow = true, 'missingMetric' => $http->missingMetric = true, default => $http->status = 403
        };

        try {
            $http->client()->runReport(new RunReportRequest('2020-01-01', ['sessions'], ['date']));
            self::fail('Expected failure');
        } catch (GoogleAnalyticsRequestFailedException $exception) {
            self::assertStringNotContainsString('test-secret', (string) $exception);
            self::assertNull($exception->getPrevious());
        }
    }
}
