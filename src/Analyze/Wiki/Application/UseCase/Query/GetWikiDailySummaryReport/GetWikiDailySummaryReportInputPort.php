<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport;

use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

interface GetWikiDailySummaryReportInputPort
{
    public function period(): ReportingPeriod;

    public function wikiIdentifier(): ?string;

    public function translationSetIdentifier(): ?string;

    public function language(): ?string;

    public function country(): ?string;
}
