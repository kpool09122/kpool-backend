<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiEngagementTimeTest extends TestCase
{
    public function testRetainsItsBusinessValueAndProvenance(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::ENGAGEMENT_SECONDS), $day, AnalyzeWikiMetrics::now(), ['sampled']);
        $value = '0.123456789';
        $result = new WikiEngagementTime($value, 3, $origin);
        self::assertSame($value, $result->totalSeconds());
        self::assertSame($origin, $result->provenance());
        self::assertSame(3, $result->sampleCount());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        foreach (['', '-1', '+1', '01', '1e3', ' 1', '1 ', '100000000000000000000', '1.1234567890'] as $value) {
            yield 'invalid:' . $value => [$value];
        }
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidSeconds(string $value): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::ENGAGEMENT_SECONDS), $day, AnalyzeWikiMetrics::now());
        $this->expectException(InvalidArgumentException::class);
        new WikiEngagementTime($value, 3, $origin);
    }

    public function testRejectsAnotherMetricDefinition(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::VIEWS), $day, AnalyzeWikiMetrics::now());
        $value = '0';
        $this->expectException(InvalidArgumentException::class);
        new WikiEngagementTime($value, 3, $origin);
    }

    public function testMissingSampleCountRemainsUnknownRatherThanZero(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::ENGAGEMENT_SECONDS), $day, AnalyzeWikiMetrics::now());
        self::assertNull((new WikiEngagementTime('1', null, $origin))->sampleCount());
        self::assertSame(0, (new WikiEngagementTime('0', 0, $origin))->sampleCount());
    }

    public function testPositiveTimeCannotBelongToAnEmptySample(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::ENGAGEMENT_SECONDS), $day, AnalyzeWikiMetrics::now());
        $this->expectException(InvalidArgumentException::class);
        new WikiEngagementTime('1', 0, $origin);
    }

    public function testNegativeSampleCountIsRejected(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::ENGAGEMENT_SECONDS), $day, AnalyzeWikiMetrics::now());
        $this->expectException(InvalidArgumentException::class);
        new WikiEngagementTime('0', -1, $origin);
    }
}
