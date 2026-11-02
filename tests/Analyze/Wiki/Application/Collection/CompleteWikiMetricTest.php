<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\Collection;

use Source\Analyze\Wiki\Application\Collection\CompleteWikiMetric;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchResult;
use Source\Analyze\Wiki\Domain\ValueObject\MetricObservation;
use Tests\TestCase;

class CompleteWikiMetricTest extends TestCase
{
    public function testSparseCompleteReportsAreJoinedByTargetWithoutCombiningDifferentCountries(): void
    {
        $jp = new MetricObservation('wiki', 'set', 'ja', 'JP', '12');
        $us = new MetricObservation('wiki', 'set', 'ja', 'US', '3');
        $views = new CompleteWikiMetric(new DailyMetricsFetchResult([$jp], []));
        $submissions = new CompleteWikiMetric(new DailyMetricsFetchResult([$us], []));
        $engagement = new CompleteWikiMetric(new DailyMetricsFetchResult([], []), true);
        self::assertCount(2, CompleteWikiMetric::targets($views, $submissions, $engagement));
        self::assertSame('12', $views->forTarget($jp)->value);
        self::assertSame('0', $views->forTarget($us)->value);
        self::assertSame('0', $submissions->forTarget($jp)->value);
        self::assertSame(0, $engagement->forTarget($jp)->denominator);
    }
}
