<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\GetVideoDetails;

use DateInterval;

final readonly class GetVideoDetailsParams
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        private array $params,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function fromArray(array $params): self
    {
        return new self($params);
    }

    /**
     * @return array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int, durationSeconds: int|null}>
     */
    public function details(): array
    {
        /** @var array<int, array{id: string, snippet: array{title: string, publishedAt: string, thumbnails: array{high?: array{url: string}, default: array{url: string}}}, statistics: array{viewCount?: string, likeCount?: string}, contentDetails?: array{duration?: string}}> $items */
        $items = $this->params['items'] ?? [];
        $details = [];

        foreach ($items as $item) {
            $details[$item['id']] = [
                'id' => $item['id'],
                'title' => $item['snippet']['title'],
                'publishedAt' => $item['snippet']['publishedAt'],
                'thumbnailUrl' => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
                'viewCount' => (int) ($item['statistics']['viewCount'] ?? 0),
                'likeCount' => (int) ($item['statistics']['likeCount'] ?? 0),
                'durationSeconds' => $this->durationSeconds($item['contentDetails']['duration'] ?? null),
            ];
        }

        return $details;
    }

    private function durationSeconds(?string $duration): ?int
    {
        if ($duration === null) {
            return null;
        }

        $interval = new DateInterval($duration);

        return $interval->d * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }
}
