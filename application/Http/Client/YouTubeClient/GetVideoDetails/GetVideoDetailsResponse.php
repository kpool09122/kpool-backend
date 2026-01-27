<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\GetVideoDetails;

final readonly class GetVideoDetailsResponse
{
    /**
     * @param array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}> $details
     */
    public function __construct(
        private array $details,
    ) {
    }

    /**
     * @return array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}>
     */
    public function details(): array
    {
        return $this->details;
    }
}
