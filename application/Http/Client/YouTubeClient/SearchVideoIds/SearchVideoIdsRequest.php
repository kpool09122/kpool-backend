<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchVideoIds;

use Psr\Http\Message\RequestInterface;

final readonly class SearchVideoIdsRequest
{
    private const string PATH = '/youtube/v3/search';

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

    public function toPsrRequest(RequestInterface $request, string $apiKey): RequestInterface
    {
        $query = http_build_query([
            'key' => $apiKey,
            'q' => $this->keyword,
            'type' => 'video',
            'part' => 'id',
            'order' => $this->order,
            'maxResults' => $this->maxResults,
        ]);

        return $request
            ->withMethod('GET')
            ->withUri($request->getUri()->withPath(self::PATH)->withQuery($query));
    }
}
