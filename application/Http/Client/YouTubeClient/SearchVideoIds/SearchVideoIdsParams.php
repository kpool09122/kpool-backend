<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchVideoIds;

final readonly class SearchVideoIdsParams
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
        /** @var array<int, array{id: array{videoId: string}}> $items */
        $items = $this->params['items'] ?? [];

        return array_map(
            static fn (array $item): string => $item['id']['videoId'],
            $items,
        );
    }
}
