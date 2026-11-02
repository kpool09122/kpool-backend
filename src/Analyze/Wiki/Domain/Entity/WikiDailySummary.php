<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\Entity;

use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\ValueObject\WikiDailySummaryIdentifier;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;

class WikiDailySummary
{
    /** @throws InvalidDailySummaryException */
    public function __construct(
        private readonly WikiDailySummaryIdentifier $identifier,
        private readonly UtcDate $day,
        private readonly WikiIdentifier $wikiIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private readonly Language $language,
        private readonly ?CountryCode $country,
        private WikiViewCount $views,
        private WikiViewerCount $viewers,
        private WikiEngagementTime $engagementTime,
        private WikiSubmissionCount $submissions,
        private WikiPublicationCount $publications,
    ) {
        $this->validateAnalysis($views, $viewers, $engagementTime, $submissions, $publications);
    }

    /** @throws InvalidDailySummaryException */
    public function replaceAnalysis(WikiViewCount $views, WikiViewerCount $viewers, WikiEngagementTime $engagementTime, WikiSubmissionCount $submissions, WikiPublicationCount $publications): void
    {
        $this->validateAnalysis($views, $viewers, $engagementTime, $submissions, $publications);
        $this->views = $views;
        $this->viewers = $viewers;
        $this->engagementTime = $engagementTime;
        $this->submissions = $submissions;
        $this->publications = $publications;
    }

    public function correctTranslationSet(TranslationSetIdentifier $translationSetIdentifier): void
    {
        $this->translationSetIdentifier = $translationSetIdentifier;
    }

    public function views(): WikiViewCount
    {
        return $this->views;
    }

    public function viewers(): WikiViewerCount
    {
        return $this->viewers;
    }

    public function engagementTime(): WikiEngagementTime
    {
        return $this->engagementTime;
    }

    public function submissions(): WikiSubmissionCount
    {
        return $this->submissions;
    }

    public function publications(): WikiPublicationCount
    {
        return $this->publications;
    }

    public function identifier(): WikiDailySummaryIdentifier
    {
        return $this->identifier;
    }

    public function day(): UtcDate
    {
        return $this->day;
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function country(): ?CountryCode
    {
        return $this->country;
    }

    /** @throws InvalidDailySummaryException */
    private function validateAnalysis(WikiViewCount $views, WikiViewerCount $viewers, WikiEngagementTime $engagementTime, WikiSubmissionCount $submissions, WikiPublicationCount $publications): void
    {
        if ((string) $views->provenance()->day() !== (string) $this->day) {
            throw new InvalidDailySummaryException('Analytical values must belong to the summary day.');
        }
        if ((string) $viewers->provenance()->day() !== (string) $this->day) {
            throw new InvalidDailySummaryException('Analytical values must belong to the summary day.');
        }
        if ((string) $engagementTime->provenance()->day() !== (string) $this->day) {
            throw new InvalidDailySummaryException('Analytical values must belong to the summary day.');
        }
        if ((string) $submissions->provenance()->day() !== (string) $this->day) {
            throw new InvalidDailySummaryException('Analytical values must belong to the summary day.');
        }
        if ((string) $publications->provenance()->day() !== (string) $this->day) {
            throw new InvalidDailySummaryException('Analytical values must belong to the summary day.');
        }
    }
}
