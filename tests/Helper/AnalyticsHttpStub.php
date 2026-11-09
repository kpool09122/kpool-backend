<?php

declare(strict_types=1);

namespace Tests\Helper;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClient;
use Google\Auth\FetchAuthTokenInterface;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Source\Analyze\Shared\Infrastructure\Client\MockGoogleAnalyticsClient;

class AnalyticsHttpStub implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];
    /** @var array<string, mixed> */
    public array $metadata = ['timeZone' => 'UTC'];
    public int $status = 200;
    /** @var list<string> */
    public array $regionalFailureDays = [];
    public bool $empty = false;
    public bool $wrongDate = false;
    public bool $paginate = false;
    public bool $paginateRegions = false;
    public bool $changingTotal = false;
    public bool $emptyContinuation = false;
    public bool $otherRow = false;
    public bool $missingMetric = false;
    /** @var array<string, string> */
    public array $dimensionOverrides = [];
    /** @var array<string, string> */
    public array $metricOverrides = [];

    public function client(): GoogleAnalyticsClient
    {
        /** @var FetchAuthTokenInterface&MockInterface $credentials */
        $credentials = Mockery::mock(FetchAuthTokenInterface::class);
        $credentials->shouldReceive('getLastReceivedToken')->andReturn(['access_token' => 'test-secret', 'expires_at' => time() + 3600]);
        $factory = new HttpFactory();

        return new GoogleAnalyticsClient('123456', $credentials, $this, new PsrFactories($factory, $factory, $factory));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $path = $request->getUri()->getPath();
        if ($request->getMethod() !== 'POST' || ! str_ends_with($path, ':runReport')) {
            throw new RuntimeException('Unexpected Analytics request.');
        }
        /** @var array{dimensions: list<array{name:string}>, metrics:list<array{name:string}>, dateRanges:list<array{startDate:string}>, offset?:string, limit?:string} $body */
        $body = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $day = str_replace('-', '', $body['dateRanges'][0]['startDate']);
        $paginate = $this->paginate || ($this->paginateRegions && in_array('region', array_column($body['dimensions'], 'name'), true));
        $dimensionValues = [];
        foreach ($body['dimensions'] as $dimension) {
            $name = $dimension['name'];
            $value = $this->dimensionOverrides[$name] ?? match ($name) {
                'date' => $this->wrongDate ? '19000101' : $day,
                'customEvent:wiki_id' => MockGoogleAnalyticsClient::WIKI_IDENTIFIER,
                'customEvent:wiki_translation_set_id' => MockGoogleAnalyticsClient::TRANSLATION_SET_IDENTIFIER,
                'customEvent:wiki_language' => 'ja',
                'countryId' => $paginate && (int) ($body['offset'] ?? '0') === 1 ? 'US' : 'JP',
                'deviceCategory' => 'mobile',
                'region' => 'Tokyo',
                default => 'example',
            };
            $dimensionValues[] = ['value' => $value];
        }
        if ($this->otherRow && $dimensionValues !== []) {
            $dimensionValues[0]['value'] = '(other)';
        }
        $metricValues = [];
        foreach ($body['metrics'] as $metric) {
            $name = $metric['name'];
            $metricValues[] = ['value' => $this->metricOverrides[$name] ?? match ($name) {
                'totalUsers' => count($body['dimensions']) === 1 ? '250' : '60',
                'eventCount' => '120', 'userEngagementDuration' => '1800.250000001', 'sessions' => '17', 'screenPageViews' => '91', default => '5',
            }];
        }
        $row = ['dimensionValues' => $dimensionValues, 'metricValues' => $this->missingMetric ? [] : $metricValues];
        $rowCount = $this->empty ? 0 : ($paginate ? 2 : 1);
        if ($this->changingTotal && (int) ($body['offset'] ?? '0') > 0) {
            $rowCount++;
        }
        $data = ['dimensionHeaders' => $body['dimensions'], 'metricHeaders' => $body['metrics'], 'metadata' => $this->metadata, 'rowCount' => $rowCount,
            'rows' => $this->empty || ($this->emptyContinuation && (int) ($body['offset'] ?? '0') > 0) || (int) ($body['offset'] ?? '0') >= $rowCount ? [] : [$row]];

        $status = in_array('region', array_column($body['dimensions'], 'name'), true) && in_array($body['dateRanges'][0]['startDate'], $this->regionalFailureDays, true) ? 503 : $this->status;

        return new Response($status, [], json_encode($data, JSON_THROW_ON_ERROR));
    }
}
