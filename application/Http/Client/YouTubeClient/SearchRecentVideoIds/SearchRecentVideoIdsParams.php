<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchRecentVideoIds;

final readonly class SearchRecentVideoIdsParams
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
     * @return string[]
     */
    public function videoIds(): array
    {
        /** @var array<int, array{id?: array{videoId?: string|null}}> $items */
        $items = $this->params['items'] ?? [];
        $videoIds = [];

        foreach ($items as $item) {
            $videoId = $item['id']['videoId'] ?? null;
            if ($videoId !== null && $videoId !== '') {
                $videoIds[] = $videoId;
            }
        }

        return $videoIds;
    }
}
