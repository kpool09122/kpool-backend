<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

class MetricProvenanceTest extends TestCase
{
    #[DataProvider('metrics')]
    public function testPreservesDefinitionHistoricalDayTimestampAndQuality(Metric $metric): void
    {
        $definition = new MetricDefinition('ga4', $metric, $metric->grain(), '1');
        $day = new UtcDate('2009-12-31');
        $now = new DateTimeImmutable('2026-11-02T00:00:00Z');
        $origin = new MetricProvenance($definition, $day, $now, ['sampled']);
        self::assertSame($definition, $origin->definition());
        self::assertSame($metric, $origin->definition()->metric);
        self::assertSame($day, $origin->day());
        self::assertSame($now, $origin->updatedAt());
        self::assertSame(['sampled'], $origin->quality());
    }

    #[DataProvider('metrics')]
    public function testRejectsRawTextAsQualityInformation(Metric $metric): void
    {
        $definition = new MetricDefinition('ga4', $metric, $metric->grain(), '1');
        $this->expectException(InvalidArgumentException::class);
        new MetricProvenance($definition, new UtcDate('2020-01-01'), new DateTimeImmutable('2026-11-02T00:00:00Z'), ['raw provider response']);
    }

    /** @return array<string, array{Metric}> */
    public static function metrics(): array
    {
        return ['app' => [Metric::APPLICATION_USERS], 'wiki' => [Metric::VIEWS]];
    }
}
