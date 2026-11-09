<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Service;

use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;

class WikiDailySummaryDataset
{
    /** @var array<string, WikiDailySummaryData> */
    private array $summariesByTarget = [];

    /** @var array<string, list<RegionalMetricObservation>> */
    private array $regionsByTarget = [];

    /**
     * @param list<WikiDailySummaryData> $summaries
     * @param list<RegionalMetricObservation> $regions
     * @throws InvalidDailySummaryException
     */
    public function __construct(
        array $summaries,
        private readonly MetricProvenance $viewsOrigin,
        private readonly MetricProvenance $viewersOrigin,
        private readonly MetricProvenance $engagementOrigin,
        private readonly MetricProvenance $submissionsOrigin,
        private readonly MetricProvenance $publicationsOrigin,
        array $regions = [],
    ) {
        foreach ($summaries as $summary) {
            if (isset($this->summariesByTarget[$summary->targetKey()])) {
                throw new InvalidDailySummaryException('Duplicate Wiki summary target.');
            }
            $this->summariesByTarget[$summary->targetKey()] = $summary;
        }
        $this->includeRegions($regions);
    }

    /** @param list<RegionalMetricObservation> $regions */
    private function includeRegions(array $regions): void
    {
        $this->regionsByTarget = [];
        foreach ($regions as $region) {
            $wikiIdentifier = new WikiIdentifier($region->wikiIdentifier);
            $language = Language::from($region->language);
            $country = $region->country === '' ? null : CountryCode::from($region->country);
            $key = WikiDailySummaryData::key($wikiIdentifier, $language, $country);
            $this->regionsByTarget[$key][] = $region;
            // A regional-only target is zero in the complete country report; do not derive country totals from regions.
            $this->summariesByTarget[$key] ??= $this->zeroTarget(
                $wikiIdentifier,
                new TranslationSetIdentifier($region->translationSetIdentifier),
                $language,
                $country,
            );
        }
    }

    /** @return list<RegionalMetricObservation> */
    public function regionsFor(WikiDailySummary $summary): array
    {
        return $this->regionsByTarget[WikiDailySummaryData::key($summary->wikiIdentifier(), $summary->language(), $summary->country())] ?? [];
    }

    public function removeFor(WikiDailySummary $summary): ?WikiDailySummaryData
    {
        $key = WikiDailySummaryData::key($summary->wikiIdentifier(), $summary->language(), $summary->country());
        $data = $this->summariesByTarget[$key] ?? null;
        unset($this->summariesByTarget[$key]);

        return $data;
    }

    /** @return list<WikiDailySummaryData> */
    public function remaining(): array
    {
        return array_values($this->summariesByTarget);
    }

    /** A former target absent from every complete source is now measured zero. */
    public function zeroFor(WikiDailySummary $summary): WikiDailySummaryData
    {
        return $this->zeroTarget($summary->wikiIdentifier(), $summary->translationSetIdentifier(), $summary->language(), $summary->country());
    }

    private function zeroTarget(WikiIdentifier $wikiIdentifier, TranslationSetIdentifier $translationSetIdentifier, Language $language, ?CountryCode $country): WikiDailySummaryData
    {
        return new WikiDailySummaryData(
            $wikiIdentifier,
            $translationSetIdentifier,
            $language,
            $country,
            new WikiViewCount('0', $this->viewsOrigin),
            new WikiViewerCount('0', $this->viewersOrigin),
            new WikiEngagementTime('0', 0, $this->engagementOrigin),
            new WikiSubmissionCount('0', $this->submissionsOrigin),
            new WikiPublicationCount('0', $this->publicationsOrigin),
        );
    }
}
