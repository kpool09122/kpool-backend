<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchResult;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\MetricObservation;

class DailyMetricsFetchResultTest extends TestCase
{
    public function testCompletedDataAndQualityArePreservedIncludingAnEmptyDataset(): void
    {
        $observations = [new MetricObservation('wiki', 'set', 'ja', 'JP', '1')];
        $result = new DailyMetricsFetchResult($observations, ['sampled']);
        self::assertSame($observations, $result->observations());
        self::assertSame(['sampled'], $result->quality());
        self::assertSame([], (new DailyMetricsFetchResult([], []))->observations());
    }

    public function testObservationsAreOrderedByTheWikiLanguageAndCountry(): void
    {
        $first = new MetricObservation('a', 'set', 'ja', 'JP', '1');
        $second = new MetricObservation('b', 'set', 'ja', 'JP', '2');
        self::assertSame([$first, $second], (new DailyMetricsFetchResult([$second, $first], []))->orderedObservations());
    }

    public function testRepeatedTargetsAreRejectedBeforeSummaryAssembly(): void
    {
        $observation = new MetricObservation('wiki', 'set', 'ja', 'JP', '1');
        $this->expectException(InvalidDailySummaryException::class);
        (new DailyMetricsFetchResult([$observation, $observation], []))->orderedObservations();
    }
}
