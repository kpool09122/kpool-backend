<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryData;
use Source\Analyze\Wiki\Application\Service\WikiDailySummaryDataset;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;

class AnalyzeWikiMetrics
{
    public static function definition(Metric $metric = Metric::VIEWS, string $source = 'test'): MetricDefinition
    {
        return new MetricDefinition($source, $metric, 'wiki_language_country', '1');
    }

    /** @return list<MetricDefinition> */
    public static function definitions(): array
    {
        return array_map(static fn (Metric $metric): MetricDefinition => self::definition($metric), [Metric::VIEWS, Metric::VIEWERS, Metric::ENGAGEMENT_SECONDS, Metric::SUBMISSIONS, Metric::PUBLICATIONS]);
    }

    /**
     * @param array<string, string> $values
     * @return array{WikiViewCount, WikiViewerCount, WikiEngagementTime, WikiSubmissionCount, WikiPublicationCount}
     */
    public static function analysisValues(UtcDate $day, array $values = []): array
    {
        $origin = static fn (Metric $metric): MetricProvenance => new MetricProvenance(self::definition($metric), $day, self::now());

        return [
            new WikiViewCount($values[Metric::VIEWS->value] ?? '5', $origin(Metric::VIEWS)),
            new WikiViewerCount($values[Metric::VIEWERS->value] ?? '5', $origin(Metric::VIEWERS)),
            new WikiEngagementTime($values[Metric::ENGAGEMENT_SECONDS->value] ?? '5', 3, $origin(Metric::ENGAGEMENT_SECONDS)),
            new WikiSubmissionCount($values[Metric::SUBMISSIONS->value] ?? '5', $origin(Metric::SUBMISSIONS)),
            new WikiPublicationCount($values[Metric::PUBLICATIONS->value] ?? '5', $origin(Metric::PUBLICATIONS)),
        ];
    }

    /** @param array<string, string> $values
     * @param list<string> $wikiIdentifiers
     */
    public static function dataset(UtcDate $day, array $values = [], array $wikiIdentifiers = ['69200000-0000-7000-8000-000000000001']): WikiDailySummaryDataset
    {
        [$views, $viewers, $engagement, $submissions, $publications] = self::analysisValues($day, $values);
        $data = [];
        foreach ($wikiIdentifiers as $wikiIdentifier) {
            $data[] = new WikiDailySummaryData(new WikiIdentifier($wikiIdentifier), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $views, $viewers, $engagement, $submissions, $publications);
        }

        return new WikiDailySummaryDataset($data, $views->provenance(), $viewers->provenance(), $engagement->provenance(), $submissions->provenance(), $publications->provenance());
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-02T00:00:00Z');
    }
}
