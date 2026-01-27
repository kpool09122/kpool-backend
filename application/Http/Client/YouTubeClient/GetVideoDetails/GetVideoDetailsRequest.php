<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\GetVideoDetails;

use Psr\Http\Message\RequestInterface;

final readonly class GetVideoDetailsRequest
{
    private const string PATH = '/youtube/v3/videos';

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

    /**
     * @param string[] $videoIdChunk
     */
    public function toPsrRequest(RequestInterface $request, string $apiKey, array $videoIdChunk): RequestInterface
    {
        $query = http_build_query([
            'key' => $apiKey,
            'id' => implode(',', $videoIdChunk),
            'part' => 'snippet,statistics',
        ]);

        return $request
            ->withMethod('GET')
            ->withUri($request->getUri()->withPath(self::PATH)->withQuery($query));
    }
}
