<?php

declare(strict_types=1);

namespace Source\Analyze\App\Infrastructure\Service;

use Application\Http\Client\GoogleAnalyticsClient\GoogleAnalyticsClientInterface;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\FilterExpression;
use Application\Http\Client\GoogleAnalyticsClient\RunReport\RunReportRequest;
use DateTimeImmutable;
use InvalidArgumentException;
use Source\Analyze\App\Application\Exception\IncompleteDailyMetricsException;
use Source\Analyze\App\Application\Exception\MetricsFetchFailedException;
use Source\Analyze\App\Application\Service\AppDailySummaryData;
use Source\Analyze\App\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\App\Application\Service\RegisteredIdentityCountQueryServiceInterface;
use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\App\Domain\ValueObject\ApplicationUserCount;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Domain\ValueObject\CountryCode;
use Throwable;

readonly class DailyMetricsFetchService implements DailyMetricsFetchServiceInterface
{
    public function __construct(
        private GoogleAnalyticsClientInterface $googleAnalyticsClient,
        private RegisteredIdentityCountQueryServiceInterface $registeredIdentityCountQueryService,
    ) {
    }

    /** @throws IncompleteDailyMetricsException
     * @throws MetricsFetchFailedException
     */
    public function fetchSummary(UtcDate $day, DateTimeImmutable $now, int $maxPages): AppDailySummaryData
    {
        try {
            $applicationUsersDefinition = new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', '1');
            $registeredUsersDefinition = new MetricDefinition('identity_history', Metric::REGISTERED_USERS, 'application_day', '1');
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
            $response = $this->googleAnalyticsClient->runReport(new RunReportRequest((string) $day, ['totalUsers'], ['date'], FilterExpression::exact('hostName', $host)));
            if (! in_array($response->params()->timeZone(), ['UTC', 'Etc/UTC', 'Etc/GMT', 'GMT'], true) || $response->rowCount() > 1 || count($response->rows()) !== $response->rowCount()) {
                throw new IncompleteDailyMetricsException('Application users require a complete UTC daily report.');
            }
            $rows = $response->rows();
            $registeredAt = $day->next()->instant();
            $registeredUsers = $this->registeredIdentityCountQueryService->countBefore($registeredAt);
            $countryUsers = $this->fetchCountryUsers($day, $maxPages, $host);
            $countryRegistered = $this->registeredIdentityCountQueryService->countByCountryBefore($registeredAt);
            $countries = [];
            foreach (array_unique([...array_keys($countryUsers), ...array_keys($countryRegistered)]) as $country) {
                $countries[] = new AppCountryMetrics(
                    $country === '' ? null : CountryCode::from($country),
                    new ApplicationUserCount($countryUsers[$country] ?? '0', new MetricProvenance($applicationUsersDefinition, $day, $now)),
                    new RegisteredUserSnapshot($countryRegistered[$country] ?? '0', $registeredAt, new MetricProvenance($registeredUsersDefinition, $day, $now)),
                );
            }

            return new AppDailySummaryData(
                new ApplicationUserCount($rows === [] ? '0' : $rows[0]->metric('totalUsers'), new MetricProvenance($applicationUsersDefinition, $day, $now)),
                new RegisteredUserSnapshot($registeredUsers, $registeredAt, new MetricProvenance($registeredUsersDefinition, $day, $now)),
                $countries,
            );
        } catch (IncompleteDailyMetricsException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new MetricsFetchFailedException($exception);
        }
    }

    /** @return array<string, string>
     * @throws IncompleteDailyMetricsException
     */
    private function fetchCountryUsers(UtcDate $day, int $maxPages, string $host): array
    {
        $offset = 0;
        $expectedRows = null;
        $countries = [];
        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->googleAnalyticsClient->runReport(new RunReportRequest(
                (string) $day,
                ['totalUsers'],
                ['date', 'countryId'],
                FilterExpression::exact('hostName', $host),
                $offset,
            ));
            if (! in_array($response->params()->timeZone(), ['UTC', 'Etc/UTC', 'Etc/GMT', 'GMT'], true)
                || ($expectedRows !== null && $expectedRows !== $response->rowCount())) {
                throw new IncompleteDailyMetricsException('App country users require complete, stable UTC reports.');
            }
            $expectedRows = $response->rowCount();
            foreach ($response->rows() as $row) {
                $raw = $row->dimension('countryId');
                $country = in_array($raw, ['', '(not set)', 'ZZ'], true) ? '' : $raw;
                if (($country !== '' && CountryCode::tryFrom($country) === null) || isset($countries[$country])) {
                    throw new IncompleteDailyMetricsException('Invalid or duplicate App country attribution.');
                }
                $countries[$country] = $row->metric('totalUsers');
            }
            $offset += count($response->rows());
            if ($offset === $expectedRows) {
                return $countries;
            }
            if ($response->rows() === []) {
                throw new IncompleteDailyMetricsException('The App country dataset is incomplete.');
            }
        }

        throw new IncompleteDailyMetricsException('App country daily page limit exceeded.');
    }
}
