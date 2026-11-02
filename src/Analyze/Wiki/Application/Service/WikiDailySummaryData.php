<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Service;

use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;

readonly class WikiDailySummaryData
{
    public function __construct(
        private WikiIdentifier $wikiIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private Language $language,
        private ?CountryCode $country,
        private WikiViewCount $views,
        private WikiViewerCount $viewers,
        private WikiEngagementTime $engagementTime,
        private WikiSubmissionCount $submissions,
        private WikiPublicationCount $publications,
    ) {
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

    public function targetKey(): string
    {
        return self::key($this->wikiIdentifier, $this->language, $this->country);
    }

    public static function key(WikiIdentifier $wikiIdentifier, Language $language, ?CountryCode $country): string
    {
        return implode("\0", [(string) $wikiIdentifier, $language->value, $country === null ? '' : $country->value]);
    }
}
