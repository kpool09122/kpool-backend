<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\Service\AppDailySummaryData;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;

class AppDailySummaryDataTest extends TestCase
{
    public function testEachRequiredAnalyticalValueHasItsOwnTypedField(): void
    {
        $values = AnalyzeAppMetrics::summaryValues(new UtcDate('2020-01-01'));
        $data = new AppDailySummaryData($values[0], $values[1], $values[2], []);
        self::assertSame($values[0], $data->applicationUsers());
        self::assertSame($values[1], $data->registeredUsers());
    }
}
