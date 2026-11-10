<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Application\UseCase\Query\PeriodComparisonReadModel;

class PeriodComparisonReadModelTest extends TestCase
{
    public function testSerializesUnavailableComparisonWithoutInventingZero(): void
    {
        $data = ['from' => '2020-01-01', 'to' => '2020-01-02', 'percentageChange' => null, 'reason' => 'zero_baseline', 'actorPercentageChange' => null, 'actorReason' => 'not_applicable'];
        $report = new PeriodComparisonReadModel(
            from: '2020-01-01',
            to: '2020-01-02',
            percentageChange: null,
            reason: 'zero_baseline',
            actorPercentageChange: null,
            actorReason: 'not_applicable',
        );
        self::assertSame($data, $report->toArray());
    }
}
