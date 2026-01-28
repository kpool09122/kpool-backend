<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken;

use Application\Http\Client\Foundation\Json\Decoder;
use Psr\Http\Message\ResponseInterface;

final readonly class ExchangeCodeForTokenResponse
{
    private string $contents;

    public function __construct(ResponseInterface $response)
    {
        $this->contents = $response->getBody()->getContents();
    }

    public function params(): ExchangeCodeForTokenParams
    {
        /** @var array<string, mixed> $data */
        $data = Decoder::decode($this->contents, true);

        return ExchangeCodeForTokenParams::fromArray($data);
    }

    public function accessToken(): string
    {
        return $this->params()->accessToken();
    }

    public function idToken(): ?string
    {
        return $this->params()->idToken();
    }
}
