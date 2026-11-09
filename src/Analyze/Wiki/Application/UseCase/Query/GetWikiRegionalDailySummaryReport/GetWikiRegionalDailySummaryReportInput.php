<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiRegionalDailySummaryReport;

use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\RegionalSelection;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

readonly class GetWikiRegionalDailySummaryReportInput implements GetWikiRegionalDailySummaryReportInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ReportingPeriod $period,
        private RegionalSelection $selection,
        private ?string $wikiIdentifier = null,
        private ?string $translationSetIdentifier = null,
        private ?string $language = null,
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

    public function wikiIdentifier(): ?string
    {
        return $this->wikiIdentifier;
    }

    public function translationSetIdentifier(): ?string
    {
        return $this->translationSetIdentifier;
    }

    public function language(): ?string
    {
        return $this->language;
    }
}
