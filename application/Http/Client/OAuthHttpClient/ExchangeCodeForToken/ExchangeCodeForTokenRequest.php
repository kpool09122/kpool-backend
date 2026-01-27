<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Source\Identity\Domain\ValueObject\SocialProvider;

final readonly class ExchangeCodeForTokenRequest
{
    public function __construct(
        private SocialProvider $provider,
        private string $code,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function code(): string
    {
        return $this->code;
    }

    /**
     * @param array<string, mixed> $providerConfig
     */
    public function toPsrRequest(
        RequestInterface $request,
        StreamFactoryInterface $streamFactory,
        array $providerConfig,
    ): RequestInterface {
        $body = http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $providerConfig['client_id'],
            'client_secret' => $providerConfig['client_secret'],
            'redirect_uri' => $providerConfig['redirect_uri'],
            'code' => $this->code,
        ]);

        /** @var string $tokenEndpoint */
        $tokenEndpoint = $providerConfig['token_endpoint'];

        return $request
            ->withMethod('POST')
            ->withUri($request->getUri()->withPath(parse_url($tokenEndpoint, PHP_URL_PATH) ?: ''))
            ->withBody($streamFactory->createStream($body))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
}
