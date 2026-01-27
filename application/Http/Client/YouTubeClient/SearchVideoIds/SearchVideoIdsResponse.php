<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchVideoIds;

final readonly class SearchVideoIdsResponse
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
