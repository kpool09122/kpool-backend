<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;

class RegionalMetricObservationTest extends TestCase
{
    public function testPreservesDecimalPrecisionAndAttribution(): void
    {
        $observation = new RegionalMetricObservation('JP', 'JP-13', 'engagement_seconds', '1800.250000001', 60, ['region_unmapped'], 'wiki', 'translation', 'ja');
        self::assertSame('1800.250000001', $observation->value);
        self::assertSame(60, $observation->denominator);
        self::assertSame('JP-13', $observation->region);
        self::assertSame(['region_unmapped'], $observation->quality);
        self::assertSame('wiki', $observation->wikiIdentifier);
        self::assertSame('translation', $observation->translationSetIdentifier);
        self::assertSame('ja', $observation->language);
    }

    /** @return iterable<string, array{string, string, ?int}> */
    public static function invalidMeasurements(): iterable
    {
        yield 'negative' => ['views', '-1', null];
        yield 'fractional count' => ['application_users', '1.5', null];
        yield 'too precise' => ['engagement_seconds', '1.1234567890', 1];
        yield 'positive duration without samples' => ['engagement_seconds', '1', 0];
        yield 'overflow' => ['registered_users', '100000000000000000000', null];
    }

    #[DataProvider('invalidMeasurements')]
    public function testRejectsInvalidMeasurements(string $metric, string $value, ?int $denominator): void
    {
        $this->expectException(RuntimeException::class);
        new RegionalMetricObservation('JP', 'JP-13', $metric, $value, $denominator);
    }

    public function testAllowsMeasuredZeroWithoutSamples(): void
    {
        self::assertSame('0.000000000', (new RegionalMetricObservation('JP', '', 'engagement_seconds', '0.000000000', 0))->value);
    }
}
