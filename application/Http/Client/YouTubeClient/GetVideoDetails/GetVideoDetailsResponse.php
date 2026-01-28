<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\GetVideoDetails;

use Application\Http\Client\Foundation\Json\Decoder;
use Psr\Http\Message\ResponseInterface;

final readonly class GetVideoDetailsResponse
{
    private string $contents;

    public function __construct(ResponseInterface $response)
    {
        $this->contents = $response->getBody()->getContents();
    }

    public function params(): GetVideoDetailsParams
    {
        /** @var array<string, mixed> $data */
        $data = Decoder::decode($this->contents, true);

        return GetVideoDetailsParams::fromArray($data);
    }

    /**
     * @return array<string, array{id: string, title: string, publishedAt: string, thumbnailUrl: string, viewCount: int, likeCount: int}>
     */
    public function details(): array
    {
        return $this->params()->details();
    }
}
