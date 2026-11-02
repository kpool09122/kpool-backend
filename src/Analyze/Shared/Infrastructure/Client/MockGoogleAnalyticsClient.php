<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Infrastructure\Client;

use Application\Http\Client\GoogleAnalyticsClient\Exceptions\GoogleAnalyticsRequestFailedException;
use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClientInterface;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\AnalyticsReportRow;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportResponse;
use GuzzleHttp\Psr7\Response;

class MockGoogleAnalyticsClient implements GoogleAnalyticsClientInterface
{
    public const string WIKI_IDENTIFIER = '69200000-0000-7000-8000-000000000001';
    public const string TRANSLATION_SET_IDENTIFIER = '69200000-0000-7000-8000-000000000002';

    public function runReport(RunReportRequest $request): RunReportResponse
    {
        $day = str_replace('-', '', $request->day());
        if ($request->dimensions() === ['date'] && $request->metrics() === ['totalUsers']) {
            $rows = [new AnalyticsReportRow(['date' => $day], ['totalUsers' => '250'])];
        } else {
            if ($request->dimensions() !== ['date', 'customEvent:wiki_id', 'customEvent:wiki_translation_set_id', 'customEvent:wiki_language', 'countryId']) {
                throw new GoogleAnalyticsRequestFailedException('Unknown mock report.');
            }
            [$japan, $us] = match ($request->metrics()) {
                ['eventCount'] => [['eventCount' => '120'], ['eventCount' => '40']],
                ['totalUsers'] => [['totalUsers' => '60'], ['totalUsers' => '20']],
                ['userEngagementDuration', 'totalUsers'] => [['userEngagementDuration' => '1800.250000001', 'totalUsers' => '60'], ['userEngagementDuration' => '300.125000001', 'totalUsers' => '20']],
                default => throw new GoogleAnalyticsRequestFailedException('Unknown mock report.'),
            };
            $dimensions = ['date' => $day, 'customEvent:wiki_id' => self::WIKI_IDENTIFIER, 'customEvent:wiki_translation_set_id' => self::TRANSLATION_SET_IDENTIFIER, 'customEvent:wiki_language' => 'ja'];
            $rows = [new AnalyticsReportRow([...$dimensions, 'countryId' => 'JP'], $japan), new AnalyticsReportRow([...$dimensions, 'countryId' => 'US'], $us)];
        }
        $data = ['dimensionHeaders' => array_map(static fn (string $name): array => ['name' => $name], $request->dimensions()),
            'metricHeaders' => array_map(static fn (string $name): array => ['name' => $name], $request->metrics()),
            'metadata' => ['timeZone' => 'UTC'], 'rowCount' => count($rows), 'rows' => []];
        foreach (array_slice($rows, $request->offset(), $request->limit()) as $row) {
            $data['rows'][] = ['dimensionValues' => array_map(static fn (string $name): array => ['value' => $row->dimension($name)], $request->dimensions()), 'metricValues' => array_map(static fn (string $name): array => ['value' => $row->metric($name)], $request->metrics())];
        }

        return new RunReportResponse(new Response(200, [], json_encode($data, JSON_THROW_ON_ERROR)), $request);
    }
}
