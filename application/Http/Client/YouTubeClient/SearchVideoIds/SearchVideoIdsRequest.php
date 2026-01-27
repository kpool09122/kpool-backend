<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchVideoIds;

final readonly class SearchVideoIdsRequest
{
    public function __construct(
        private string $keyword,
        private string $order,
        private int $maxResults,
    ) {
    }

    public function keyword(): string
    {
        return $this->keyword;
    }

    public function order(): string
    {
        return $this->order;
    }

    public function maxResults(): int
    {
        return $this->maxResults;
    }
}
