<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Infrastructure\Service;

use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClientInterface;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\FilterExpression;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use DateTimeImmutable;
use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Service\RegionalMetricsFetchService;
use Source\Analyze\Wiki\Application\Collection\CompleteWikiMetric;
use Source\Analyze\Wiki\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\Wiki\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchResult;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Application\Service\DailyWikiOperationsQueryServiceInterface;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryData;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryDataset;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\MetricObservation;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Throwable;

readonly class DailyMetricsFetchService implements DailyMetricsFetchServiceInterface
{
    private const string PUBLIC_WIKI_PATH_REGEX = '^/((ja|en|ko)/wiki|wiki/(ja|en|ko))/[^/]+/?$';

    private const string WIKI_CREATE_PATH_REGEX = '^/wiki/(ja|en|ko)/edit/?$';

    public function __construct(
        private GoogleAnalyticsClientInterface $googleAnalyticsClient,
        private DailyWikiOperationsQueryServiceInterface $dailyWikiOperationsQueryService,
        private RegionalMetricsFetchService $regionalMetricsFetchService,
    ) {
    }

    /** @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     * @throws InvalidDailySummaryException
     */
    public function fetchSummary(UtcDate $day, DateTimeImmutable $now, int $maxPages): WikiDailySummaryDataset
    {
        try {
            $viewsDefinition = new MetricDefinition('ga4', Metric::VIEWS, 'wiki_language_country', '1');
            $viewersDefinition = new MetricDefinition('ga4', Metric::VIEWERS, 'wiki_language_country', '1');
            $engagementTimeDefinition = new MetricDefinition('ga4', Metric::ENGAGEMENT_SECONDS, 'wiki_language_country', '1');
            $submissionsDefinition = new MetricDefinition('wiki_history', Metric::SUBMISSIONS, 'wiki_language_country', '1');
            $publicationsDefinition = new MetricDefinition('wiki_history', Metric::PUBLICATIONS, 'wiki_language_country', '1');
            if ($maxPages < 1) {
                throw new IncompleteDailyMetricsException('Invalid daily page limit.');
            }
            $frontendUrl = config('app.frontend_url');
            if (! is_string($frontendUrl) || filter_var($frontendUrl, FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException('A valid frontend URL must be configured.');
            }
            $host = parse_url($frontendUrl, PHP_URL_HOST);
            if (! is_string($host) || $host === '' || ! in_array(parse_url($frontendUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw new InvalidArgumentException('The frontend URL must use HTTP or HTTPS and contain a host.');
            }
            $viewsResult = $this->fetchAnalyticsMetric($viewsDefinition, 'eventCount', true, $day, $maxPages, $host);
            $viewersResult = $this->fetchAnalyticsMetric($viewersDefinition, 'totalUsers', true, $day, $maxPages, $host);
            $engagementTimeResult = $this->fetchAnalyticsMetric($engagementTimeDefinition, 'userEngagementDuration', false, $day, $maxPages, $host);
            $submissions = [];
            $publications = [];
            foreach ($this->dailyWikiOperationsQueryService->find($day->instant(), $day->next()->instant()) as $count) {
                // Operations without a published target are reported directly from Wiki history.
                if ($count->wikiIdentifier() === null || $count->translationSetIdentifier() === null || $count->language() === null) {
                    continue;
                }
                $submission = new MetricObservation($count->wikiIdentifier(), $count->translationSetIdentifier(), $count->language(), $count->country(), $count->submissions());
                $publication = new MetricObservation($count->wikiIdentifier(), $count->translationSetIdentifier(), $count->language(), $count->country(), $count->publications());
                $submission->validateFor($submissionsDefinition, $day);
                $publication->validateFor($publicationsDefinition, $day);
                $submissions[] = $submission;
                $publications[] = $publication;
            }
            $submissionsResult = new DailyMetricsFetchResult($submissions, []);
            $publicationsResult = new DailyMetricsFetchResult($publications, []);
            $viewsOrigin = new MetricProvenance($viewsDefinition, $day, $now, $viewsResult->quality());
            $viewersOrigin = new MetricProvenance($viewersDefinition, $day, $now, $viewersResult->quality());
            $engagementTimeOrigin = new MetricProvenance($engagementTimeDefinition, $day, $now, $engagementTimeResult->quality());
            $submissionsOrigin = new MetricProvenance($submissionsDefinition, $day, $now, $submissionsResult->quality());
            $publicationsOrigin = new MetricProvenance($publicationsDefinition, $day, $now, $publicationsResult->quality());
            $views = new CompleteWikiMetric($viewsResult);
            $viewers = new CompleteWikiMetric($viewersResult);
            $engagementTime = new CompleteWikiMetric($engagementTimeResult, true);
            $submissions = new CompleteWikiMetric($submissionsResult);
            $publications = new CompleteWikiMetric($publicationsResult);
            $targets = CompleteWikiMetric::targets($views, $viewers, $engagementTime, $submissions, $publications);
            $summaries = [];
            foreach ($targets as $target) {
                $view = $views->forTarget($target);
                $viewer = $viewers->forTarget($target);
                $engagement = $engagementTime->forTarget($target);
                $submission = $submissions->forTarget($target);
                $publication = $publications->forTarget($target);
                $summaries[] = new WikiDailySummaryData(
                    new WikiIdentifier($view->targetIdentifier),
                    new TranslationSetIdentifier($view->translationSetIdentifier),
                    Language::from($view->language),
                    $view->country === '' ? null : CountryCode::from($view->country),
                    new WikiViewCount($view->value, $viewsOrigin),
                    new WikiViewerCount($viewer->value, $viewersOrigin),
                    new WikiEngagementTime($engagement->value, $engagement->denominator, $engagementTimeOrigin),
                    new WikiSubmissionCount($submission->value, $submissionsOrigin),
                    new WikiPublicationCount($publication->value, $publicationsOrigin),
                );
            }

            return new WikiDailySummaryDataset($summaries, $viewsOrigin, $viewersOrigin, $engagementTimeOrigin, $submissionsOrigin, $publicationsOrigin, $this->fetchRegions($day, $maxPages));
        } catch (IncompleteDailyMetricsException|InvalidDailySummaryException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new MetricsFetchFailedException($exception);
        }
    }

    /** @return list<RegionalMetricObservation> */
    private function fetchRegions(UtcDate $day, int $maxPages): array
    {
        $rows = [];
        foreach (['views' => 'eventCount', 'viewers' => 'totalUsers', 'engagement_seconds' => 'userEngagementDuration'] as $metric => $gaMetric) {
            array_push($rows, ...$this->regionalMetricsFetchService->fetch('wiki', $metric, $gaMetric, $day, $maxPages));
        }

        return $rows;
    }

    /** @throws IncompleteDailyMetricsException
     */
    private function fetchAnalyticsMetric(MetricDefinition $definition, string $metric, bool $pageViewsOnly, UtcDate $day, int $maxPages, string $host): DailyMetricsFetchResult
    {
        $filters = [FilterExpression::exact('hostName', $host),
            FilterExpression::fullRegexp('pagePath', self::PUBLIC_WIKI_PATH_REGEX),
            FilterExpression::negate(FilterExpression::fullRegexp('pagePath', self::WIKI_CREATE_PATH_REGEX)),
            FilterExpression::negate(FilterExpression::inList('customEvent:wiki_id', ['', '(not set)']))];
        if ($pageViewsOnly) {
            $filters[] = FilterExpression::exact('eventName', 'page_view');
        }
        $metrics = $pageViewsOnly ? [$metric] : [$metric, 'totalUsers'];
        $offset = 0;
        $expectedRows = null;
        $observations = [];
        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->googleAnalyticsClient->runReport(new RunReportRequest(
                (string) $day,
                $metrics,
                ['date', 'customEvent:wiki_id', 'customEvent:wiki_translation_set_id', 'customEvent:wiki_language', 'countryId'],
                FilterExpression::all(...$filters),
                $offset
            ));
            if (! in_array($response->params()->timeZone(), ['UTC', 'Etc/UTC', 'Etc/GMT', 'GMT'], true) || ($expectedRows !== null && $expectedRows !== $response->rowCount())) {
                throw new IncompleteDailyMetricsException('Wiki metrics require complete, stable UTC reports.');
            }
            $expectedRows = $response->rowCount();
            foreach ($response->rows() as $row) {
                $wiki = $row->dimension('customEvent:wiki_id');
                $translationSet = $row->dimension('customEvent:wiki_translation_set_id');
                $language = $row->dimension('customEvent:wiki_language');
                foreach ([$wiki, $translationSet, $language] as $identifier) {
                    if ($identifier === '' || $identifier === '(not set)') {
                        throw new IncompleteDailyMetricsException('Wiki attribution is incomplete.');
                    }
                }
                $country = $row->dimension('countryId');
                $denominator = null;
                if (! $pageViewsOnly) {
                    $samples = $row->metric('totalUsers');
                    if (! ctype_digit($samples) || strlen($samples) > strlen((string) PHP_INT_MAX)
                        || (strlen($samples) === strlen((string) PHP_INT_MAX) && strcmp($samples, (string) PHP_INT_MAX) > 0)) {
                        throw new IncompleteDailyMetricsException('Invalid engagement denominator.');
                    }
                    $denominator = (int) $samples;
                }
                $observation = new MetricObservation($wiki, $translationSet, $language, $country === '(not set)' ? '' : $country, $row->metric($metric), $denominator);
                $observation->validateFor($definition, $day);
                $observations[] = $observation;
            }
            $offset += count($response->rows());
            if ($offset === $expectedRows) {
                return new DailyMetricsFetchResult($observations, []);
            }
            if ($response->rows() === []) {
                throw new IncompleteDailyMetricsException('The Wiki daily dataset is incomplete.');
            }
        }

        throw new IncompleteDailyMetricsException('Daily page limit exceeded.');
    }
}
