<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Infrastructure\Factory;

use Source\Analyze\Wiki\Domain\Entity\WikiDailySummary;
use Source\Analyze\Wiki\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\Wiki\Domain\Factory\WikiDailySummaryFactoryInterface;
use Source\Analyze\Wiki\Domain\ValueObject\WikiDailySummaryIdentifier;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiPublicationCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiSubmissionCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;

readonly class WikiDailySummaryFactory implements WikiDailySummaryFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    /** @throws InvalidDailySummaryException */
    public function create(WikiIdentifier $wikiIdentifier, TranslationSetIdentifier $translationSetIdentifier, Language $language, ?CountryCode $country, WikiViewCount $views, WikiViewerCount $viewers, WikiEngagementTime $engagementTime, WikiSubmissionCount $submissions, WikiPublicationCount $publications): WikiDailySummary
    {
        return new WikiDailySummary(
            new WikiDailySummaryIdentifier($this->uuidGenerator->generate()),
            $views->provenance()->day(),
            $wikiIdentifier,
            $translationSetIdentifier,
            $language,
            $country,
            $views,
            $viewers,
            $engagementTime,
            $submissions,
            $publications,
        );
    }
}
