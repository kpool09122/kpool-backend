<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\SearchVideoIds;

use Application\Http\Client\Foundation\Json\Decoder;
use Psr\Http\Message\ResponseInterface;

final readonly class SearchVideoIdsResponse
{
    private string $contents;

    public function __construct(ResponseInterface $response)
    {
        $this->contents = $response->getBody()->getContents();
    }

    public function params(): SearchVideoIdsParams
    {
        /** @var array<string, mixed> $data */
        $data = Decoder::decode($this->contents, true);

        return SearchVideoIdsParams::fromArray($data);
    }

    /**
     * @return string[]
     */
    public function videoIds(): array
    {
        return $this->params()->videoIds();
    }
}
