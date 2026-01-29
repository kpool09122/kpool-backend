<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\FetchUser;

use Application\Http\Client\Foundation\Json\Decoder;
use Psr\Http\Message\ResponseInterface;

final readonly class FetchUserResponse
{
    private string $contents;

    public function __construct(ResponseInterface $response)
    {
        $this->contents = $response->getBody()->getContents();
    }

    public function params(): FetchUserParams
    {
        /** @var array<string, mixed> $data */
        $data = Decoder::decode($this->contents, true);

        return FetchUserParams::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->params()->data();
    }
}
