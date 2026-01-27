<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchRecentVideoIds;

final readonly class SearchRecentVideoIdsResponse
{
    /**
     * @param string[] $videoIds
     */
    public function __construct(
        private array $videoIds,
    ) {
    }

    /**
     * @return string[]
     */
    public function videoIds(): array
    {
        return $this->videoIds;
    }
}
