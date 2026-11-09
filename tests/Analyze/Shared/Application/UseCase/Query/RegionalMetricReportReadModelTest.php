<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Application\UseCase\Query\RegionalMetricReportReadModel;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;

class RegionalMetricReportReadModelTest extends TestCase
{
    public function testRegionalContractHasNoCountryBreakdown(): void
    {
        $metric = new MetricReportReadModel(new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', '1'), null, [], null, null);
        $expected = $metric->toArray();
        unset($expected['countries']);
        self::assertSame([...$expected, 'regions' => []], (new RegionalMetricReportReadModel($metric, []))->toArray());
    }
}
