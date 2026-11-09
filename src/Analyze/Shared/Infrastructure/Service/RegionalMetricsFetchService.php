<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Infrastructure\Service;

use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClientInterface;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\FilterExpression;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use RuntimeException;
use Source\Analyze\Shared\Application\Service\RegionNormalizerInterface;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Throwable;

/** Shared GA4 regional acquisition only; publication belongs to the importing use case. */
readonly class RegionalMetricsFetchService
{
    public function __construct(
        private GoogleAnalyticsClientInterface $googleAnalyticsClient,
        private RegionNormalizerInterface $regionNormalizer,
    ) {
    }

    /** @return list<RegionalMetricObservation>
     * @throws Throwable
     */
    public function fetch(string $context, string $metric, string $gaMetric, UtcDate $day, int $maxPages): array
    {
        $url = config('app.frontend_url');
        $host = is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false ? parse_url($url, PHP_URL_HOST) : null;
        if (! is_string($host) || $host === '' || $maxPages < 1) {
            throw new RuntimeException('Invalid regional import configuration.');
        }
        $filters = [FilterExpression::exact('hostName', $host)];
        $dimensions = ['date', 'countryId', 'region'];
        if ($context === 'wiki') {
            array_push($dimensions, 'customEvent:wiki_id', 'customEvent:wiki_translation_set_id', 'customEvent:wiki_language');
            $filters[] = FilterExpression::fullRegexp('pagePath', '^/((ja|en|ko)/wiki|wiki/(ja|en|ko))/[^/]+/?$');
            $filters[] = FilterExpression::negate(FilterExpression::fullRegexp('pagePath', '^/wiki/(ja|en|ko)/edit/?$'));
            $filters[] = FilterExpression::negate(FilterExpression::inList('customEvent:wiki_id', ['', '(not set)']));
            if ($metric !== 'engagement_seconds') {
                $filters[] = FilterExpression::exact('eventName', 'page_view');
            }
        }
        $metrics = $metric === 'engagement_seconds' ? [$gaMetric, 'totalUsers'] : [$gaMetric];
        $offset = 0;
        $expected = null;
        $rows = [];
        $seen = [];
        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->googleAnalyticsClient->runReport(new RunReportRequest((string) $day, $metrics, $dimensions, FilterExpression::all(...$filters), $offset));
            if (! in_array($response->params()->timeZone(), ['UTC', 'Etc/UTC', 'Etc/GMT', 'GMT'], true) || ($expected !== null && $expected !== $response->rowCount())) {
                throw new RuntimeException('Regional metrics require a complete stable UTC report.');
            }
            $expected = $response->rowCount();
            foreach ($response->rows() as $observation) {
                $rawCountry = $observation->dimension('countryId');
                $country = in_array($rawCountry, ['', '(not set)', 'ZZ'], true) ? null : CountryCode::from($rawCountry);
                $rawRegion = $observation->dimension('region');
                $region = $country === null ? null : $this->regionNormalizer->normalize($country, $rawRegion);
                $quality = $region === null ? [in_array($rawRegion, ['', '(not set)'], true) ? 'region_unknown' : 'region_unmapped'] : [];
                $denominator = null;
                if ($metric === 'engagement_seconds') {
                    $samples = $observation->metric('totalUsers');
                    if (! ctype_digit($samples) || strlen($samples) > 18) {
                        throw new RuntimeException('Invalid regional engagement denominator.');
                    }
                    $denominator = (int) $samples;
                }
                $row = new RegionalMetricObservation(
                    $country->value ?? '',
                    $region !== null ? (string) $region : '',
                    $metric,
                    $observation->metric($gaMetric),
                    $denominator,
                    $quality,
                    $context === 'wiki' ? (string) new WikiIdentifier($observation->dimension('customEvent:wiki_id')) : '',
                    $context === 'wiki' ? (string) new TranslationSetIdentifier($observation->dimension('customEvent:wiki_translation_set_id')) : '',
                    $context === 'wiki' ? Language::from($observation->dimension('customEvent:wiki_language'))->value : '',
                );
                $key = implode("\0", [$row->country, $row->region, $row->wikiIdentifier, $row->language]);
                if (isset($seen[$key])) {
                    throw new RuntimeException('Ambiguous or duplicate normalized regional attribution.');
                }
                $seen[$key] = true;
                $rows[] = $row;
            }
            $offset += count($response->rows());
            if ($offset === $expected) {
                return $rows;
            }
            if ($offset > $expected || $response->rows() === []) {
                throw new RuntimeException('Incomplete regional report pagination.');
            }
        }

        throw new RuntimeException('Regional report page limit exceeded.');
    }
}
