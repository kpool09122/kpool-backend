<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\Factory;

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

interface WikiDailySummaryFactoryInterface
{
    /** @throws InvalidDailySummaryException */
    public function create(WikiIdentifier $wikiIdentifier, TranslationSetIdentifier $translationSetIdentifier, Language $language, ?CountryCode $country, WikiViewCount $views, WikiViewerCount $viewers, WikiEngagementTime $engagementTime, WikiSubmissionCount $submissions, WikiPublicationCount $publications): WikiDailySummary;
}
