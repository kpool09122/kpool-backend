<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

readonly class GetAppDailySummaryReportInput implements GetAppDailySummaryReportInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ReportingPeriod $period,
        private ?string $country = null,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function period(): ReportingPeriod
    {
        return $this->period;
    }
}
