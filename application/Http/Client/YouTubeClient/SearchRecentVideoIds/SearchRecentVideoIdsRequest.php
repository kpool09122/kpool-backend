<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchRecentVideoIds;

use Carbon\CarbonImmutable;
use Psr\Http\Message\RequestInterface;

final readonly class SearchRecentVideoIdsRequest
{
    private const string PATH = '/youtube/v3/search';

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

    public function toPsrRequest(RequestInterface $request, string $apiKey): RequestInterface
    {
        $query = http_build_query([
            'key' => $apiKey,
            'q' => $this->keyword,
            'type' => 'video',
            'part' => 'id',
            'order' => 'viewCount',
            'publishedAfter' => $this->publishedAfter->format('c'),
            'maxResults' => $this->maxResults,
        ]);

        return $request
            ->withMethod('GET')
            ->withUri($request->getUri()->withPath(self::PATH)->withQuery($query));
    }
}
