<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Application\Service;

use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Application\UseCase\Query\PeriodComparisonReadModel;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPreviousPeriod;

interface PeriodComparisonCalculatorInterface
{
    public function calculate(MetricReportReadModel $current, MetricReportReadModel $previous, ReportingPreviousPeriod $previousPeriod): PeriodComparisonReadModel;
}
