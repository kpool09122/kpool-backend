<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\Metric;

class MetricTest extends TestCase
{
    #[DataProvider('metrics')]
    public function testSupportedMetricsKeepTheirStoredValueGrainAndAggregation(Metric $metric, string $value, string $grain, bool $snapshot): void
    {
        self::assertSame($value, $metric->value);
        self::assertSame($grain, $metric->grain());
        self::assertSame($snapshot, $metric->isSnapshot());
    }

    /** @return array<string, array{Metric, string, string, bool}> */
    public static function metrics(): array
    {
        return [
            'application users' => [Metric::APPLICATION_USERS, 'application_users', 'application_day', false],
            'registered users' => [Metric::REGISTERED_USERS, 'registered_users', 'application_day', true],
            'views' => [Metric::VIEWS, 'wiki_views', 'wiki_language_country', false],
            'viewers' => [Metric::VIEWERS, 'wiki_viewers', 'wiki_language_country', false],
            'engagement time' => [Metric::ENGAGEMENT_SECONDS, 'wiki_engagement_seconds', 'wiki_language_country', false],
            'submissions' => [Metric::SUBMISSIONS, 'wiki_submissions', 'wiki_language_country', false],
            'publications' => [Metric::PUBLICATIONS, 'wiki_publications', 'wiki_language_country', false],
        ];
    }
}
