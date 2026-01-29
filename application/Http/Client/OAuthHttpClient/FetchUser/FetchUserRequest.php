<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\FetchUser;

use Psr\Http\Message\RequestInterface;
use Source\Identity\Domain\ValueObject\SocialProvider;

final readonly class FetchUserRequest
{
    public function __construct(
        private SocialProvider $provider,
        private string $accessToken,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param array<string, mixed> $providerConfig
     */
    public function toPsrRequest(
        RequestInterface $request,
        array $providerConfig,
    ): RequestInterface {
        /** @var string $userinfoEndpoint */
        $userinfoEndpoint = $providerConfig['userinfo_endpoint'];

        return $request
            ->withMethod('GET')
            ->withUri($request->getUri()->withPath(parse_url($userinfoEndpoint, PHP_URL_PATH) ?: ''))
            ->withHeader('Authorization', 'Bearer '.$this->accessToken);
    }
}
