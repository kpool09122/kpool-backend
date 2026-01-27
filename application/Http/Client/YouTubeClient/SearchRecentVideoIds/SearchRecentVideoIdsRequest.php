<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchRecentVideoIds;

use Carbon\CarbonImmutable;

final readonly class SearchRecentVideoIdsRequest
{
    public function __construct(
        private string $keyword,
        private int $maxResults,
        private CarbonImmutable $publishedAfter,
    ) {
    }

    public function keyword(): string
    {
        return $this->keyword;
    }

    public function maxResults(): int
    {
        return $this->maxResults;
    }

    public function publishedAfter(): CarbonImmutable
    {
        return $this->publishedAfter;
    }
}
