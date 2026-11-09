<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query\GetAppDailySummaryReport;

use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

interface GetAppDailySummaryReportInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function country(): ?string;

    public function period(): ReportingPeriod;
}
