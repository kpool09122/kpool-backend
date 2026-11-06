<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Service;

use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;

class WikiDailySummaryDataset
{
    /** @var array<string, WikiDailySummaryData> */
    private array $summariesByTarget = [];

    /**
     * @param list<WikiDailySummaryData> $summaries
     * @throws InvalidDailySummaryException
     */
    public function __construct(
        array $summaries,
        private readonly MetricProvenance $viewsOrigin,
        private readonly MetricProvenance $viewersOrigin,
        private readonly MetricProvenance $engagementOrigin,
        private readonly MetricProvenance $submissionsOrigin,
        private readonly MetricProvenance $publicationsOrigin,
    ) {
        foreach ($summaries as $summary) {
            if (isset($this->summariesByTarget[$summary->targetKey()])) {
                throw new InvalidDailySummaryException('Duplicate Wiki summary target.');
            }
            $this->summariesByTarget[$summary->targetKey()] = $summary;
        }
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
        return new WikiDailySummaryData(
            $summary->wikiIdentifier(),
            $summary->translationSetIdentifier(),
            $summary->language(),
            $summary->country(),
            new WikiViewCount('0', $this->viewsOrigin),
            new WikiViewerCount('0', $this->viewersOrigin),
            new WikiEngagementTime('0', 0, $this->engagementOrigin),
            new WikiSubmissionCount('0', $this->submissionsOrigin),
            new WikiPublicationCount('0', $this->publicationsOrigin),
        );
    }
}
