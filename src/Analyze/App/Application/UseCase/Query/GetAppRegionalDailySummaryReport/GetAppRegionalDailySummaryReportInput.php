<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppRegionalDailySummaryReport;

use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

readonly class GetAppRegionalDailySummaryReportInput implements GetAppRegionalDailySummaryReportInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ReportingPeriod $period,
        private RegionalSelection $selection,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function period(): ReportingPeriod
    {
        return $this->period;
    }

    public function selection(): RegionalSelection
    {
        return $this->selection;
    }

    public function country(): string
    {
        return $this->selection->country->value;
    }
}
