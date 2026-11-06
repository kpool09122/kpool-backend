<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;

class MetricDefinitionTest extends TestCase
{
    #[DataProvider('metrics')]
    public function testDefinitionPreservesTheMetricAndComparesAllValues(Metric $metric): void
    {
        $definition = new MetricDefinition('ga4', $metric, $metric->grain(), '1');
        $same = new MetricDefinition('ga4', $metric, $metric->grain(), '1');
        self::assertSame($metric, $definition->metric);
        $definition->assertMatches($same);
        self::assertEquals($definition, $same);
        $this->expectException(MetricDefinitionMismatchException::class);
        $definition->assertMatches(new MetricDefinition('ga4', $metric, $metric->grain(), '2'));
    }

    #[DataProvider('metrics')]
    public function testInvalidSourceIsRejected(Metric $metric): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MetricDefinition('bad/key', $metric, $metric->grain(), '1');
    }

    #[DataProvider('metrics')]
    public function testMetricCannotUseTheOtherSubdomainsGrain(Metric $metric): void
    {
        $otherGrain = $metric->grain() === 'application_day' ? 'wiki_language_country' : 'application_day';
        $this->expectException(InvalidArgumentException::class);
        new MetricDefinition('ga4', $metric, $otherGrain, '1');
    }

    public function testAppAndWikiDefinitionsDoNotMatch(): void
    {
        $app = new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', '1');
        $wiki = new MetricDefinition('ga4', Metric::VIEWS, 'wiki_language_country', '1');
        $this->expectException(MetricDefinitionMismatchException::class);
        $app->assertMatches($wiki);
    }

    /** @return array<string, array{Metric}> */
    public static function metrics(): array
    {
        return ['app' => [Metric::APPLICATION_USERS], 'wiki' => [Metric::VIEWS]];
    }
}
