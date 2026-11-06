<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiPublicationCountTest extends TestCase
{
    public function testRetainsItsBusinessValueAndProvenance(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::PUBLICATIONS), $day, AnalyzeWikiMetrics::now(), ['sampled']);
        $value = '99999999999999999999';
        $result = new WikiPublicationCount($value, $origin);
        self::assertSame($value, $result->count());
        self::assertSame($origin, $result->provenance());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        foreach (['', '-1', '+1', '01', '1e3', ' 1', '1 ', '100000000000000000000', '1.5'] as $value) {
            yield 'invalid:' . $value => [$value];
        }
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidCounts(string $value): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::PUBLICATIONS), $day, AnalyzeWikiMetrics::now());
        $this->expectException(InvalidArgumentException::class);
        new WikiPublicationCount($value, $origin);
    }

    public function testRejectsAnotherMetricDefinition(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeWikiMetrics::definition(Metric::VIEWS), $day, AnalyzeWikiMetrics::now());
        $value = '0';
        $this->expectException(InvalidArgumentException::class);
        new WikiPublicationCount($value, $origin);
    }
}
