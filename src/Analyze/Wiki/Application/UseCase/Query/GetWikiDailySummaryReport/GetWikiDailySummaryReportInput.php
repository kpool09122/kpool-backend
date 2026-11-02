<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport;

use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;

readonly class GetWikiDailySummaryReportInput implements GetWikiDailySummaryReportInputPort
{
    public function __construct(
        private MetricDefinition $definition,
        private ReportingPeriod $period,
        private ?string $wikiIdentifier = null,
        private ?string $translationSetIdentifier = null,
        private ?string $language = null,
        private ?string $country = null,
    ) {

    }

    public function definition(): MetricDefinition
    {
        return $this->definition;
    }

    public function period(): ReportingPeriod
    {
        return $this->period;
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

    public function country(): ?string
    {
        return $this->country;
    }
}
