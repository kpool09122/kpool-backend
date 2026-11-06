<?php

declare(strict_types=1);

namespace Tests\Application\Http\Client\GoogleAnalyticsClient\RunReport;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportResponse;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Tests\TestCase;

class RunReportResponseTest extends TestCase
{
    public function testHttpBodyBecomesValidatedParamsAndTypedRows(): void
    {
        $data = ['dimensionHeaders' => [['name' => 'date']], 'metricHeaders' => [['name' => 'totalUsers']], 'metadata' => ['timeZone' => 'UTC'], 'rowCount' => 1,
            'rows' => [['dimensionValues' => [['value' => '20200101']], 'metricValues' => [['value' => '250']]]]];
        $response = new RunReportResponse(new Response(200, [], json_encode($data, JSON_THROW_ON_ERROR)), new RunReportRequest('2020-01-01', ['totalUsers'], ['date']));
        self::assertSame(1, $response->params()->rowCount());
        self::assertSame('250', $response->params()->rows()[0]->metric('totalUsers'));
        self::assertSame($response->params()->rows(), $response->rows());
    }

    public function testUnreadableHttpBodyIsSanitized(): void
    {
        /** @var StreamInterface&MockInterface $stream */
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->once()->andThrow(new RuntimeException('secret'));

        try {
            new RunReportResponse(new Response(200, [], $stream), new RunReportRequest('2020-01-01', ['totalUsers'], ['date']));
            self::fail('Expected unreadable response.');
        } catch (GoogleAnalyticsRequestFailedException $exception) {
            self::assertStringNotContainsString('secret', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidBodies(): iterable
    {
        yield 'malformed JSON' => ['{"secret":'];
        yield 'null' => ['null'];
        yield 'list' => ['[]'];
        yield 'string' => ['"secret"'];
    }

    #[DataProvider('invalidBodies')]
    public function testInvalidJsonIsSanitizedBeforeExposingParams(string $body): void
    {
        try {
            new RunReportResponse(new Response(200, [], $body), new RunReportRequest('2020-01-01', ['totalUsers'], ['date']));
            self::fail('Expected invalid response.');
        } catch (GoogleAnalyticsRequestFailedException $exception) {
            self::assertStringNotContainsString('secret', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }
}
