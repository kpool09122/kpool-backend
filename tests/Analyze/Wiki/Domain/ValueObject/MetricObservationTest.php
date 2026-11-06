<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\ValueObject\MetricObservation;
use Tests\Helper\AnalyzeWikiMetrics;

class MetricObservationTest extends TestCase
{
    public function testUnroundedDurationAndDenominatorArePreserved(): void
    {
        $observation = new MetricObservation('wiki', 'set', 'ja', '', '0.123456789', 7);
        $observation->validateFor(AnalyzeWikiMetrics::definition(Metric::ENGAGEMENT_SECONDS), new UtcDate('2020-01-01'));
        self::assertSame('0.123456789', $observation->value);
        self::assertSame(7, $observation->denominator);
    }

    public function testNegativeMetricIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MetricObservation('wiki', 'set', 'ja', 'JP', '-1');
    }

    public function testRejectsAppMetric(): void
    {
        $definition = new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', 'v1');
        $observation = new MetricObservation('wiki', 'set', 'ja', '', '1');
        $this->expectException(InvalidArgumentException::class);
        $observation->validateFor($definition, new UtcDate('2020-01-01'));
    }
}
