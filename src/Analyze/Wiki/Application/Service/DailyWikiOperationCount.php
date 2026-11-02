<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\Service;

class DailyWikiOperationCount
{
    private int $submissions = 0;
    private int $publications = 0;

    public function __construct(private readonly string $wikiIdentifier, private readonly string $translationSetIdentifier, private readonly string $language, private readonly string $country)
    {
    }

    public function recordSubmission(): void
    {
        $this->submissions++;
    }

    public function recordPublication(): void
    {
        $this->publications++;
    }

    public function wikiIdentifier(): string
    {
        return $this->wikiIdentifier;
    }

    public function translationSetIdentifier(): string
    {
        return $this->translationSetIdentifier;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function submissions(): string
    {
        return (string) $this->submissions;
    }

    public function publications(): string
    {
        return (string) $this->publications;
    }
}
