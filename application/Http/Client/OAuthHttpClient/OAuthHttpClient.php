<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\OAuthHttpClient\Exceptions\OAuthException;
use Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken\ExchangeCodeForTokenRequest;
use Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken\ExchangeCodeForTokenResponse;
use Application\Http\Client\OAuthHttpClient\FetchUser\FetchUserRequest;
use Application\Http\Client\OAuthHttpClient\FetchUser\FetchUserResponse;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Source\Identity\Domain\ValueObject\SocialProvider;

class OAuthHttpClient
{
    /**
     * @param array<string, array<string, mixed>> $config
     */
    public function __construct(
        private readonly UriInterface $uri,
        private readonly ClientInterface $client,
        private readonly PsrFactories $psrFactories,
        private readonly array $config,
    ) {
    }

    /**
     * @throws OAuthException
     */
    public function exchangeCodeForToken(ExchangeCodeForTokenRequest $request): ExchangeCodeForTokenResponse
    {
        $providerConfig = $this->getProviderConfig($request->provider());

        /** @var string $tokenEndpoint */
        $tokenEndpoint = $providerConfig['token_endpoint'];

        $baseRequest = $this->createBaseRequest($tokenEndpoint);
        $psrRequest = $request->toPsrRequest(
            $baseRequest,
            $this->psrFactories->getStreamFactory(),
            $providerConfig,
        );

        $response = $this->sendRequest($psrRequest);

        if ($response->getStatusCode() >= 400) {
            throw new OAuthException(
                sprintf('Failed to exchange code for token: %s', $response->getBody()->getContents()),
            );
        }

        return new ExchangeCodeForTokenResponse($response);
    }

    /**
     * @throws OAuthException
     */
    public function fetchUser(FetchUserRequest $request): FetchUserResponse
    {
        $providerConfig = $this->getProviderConfig($request->provider());

        /** @var string $userinfoEndpoint */
        $userinfoEndpoint = $providerConfig['userinfo_endpoint'];

        $baseRequest = $this->createBaseRequest($userinfoEndpoint);
        $psrRequest = $request->toPsrRequest($baseRequest, $providerConfig);

        $response = $this->sendRequest($psrRequest);

        if ($response->getStatusCode() >= 400) {
            throw new OAuthException(
                sprintf('Failed to fetch user info: %s', $response->getBody()->getContents()),
            );
        }

        return new FetchUserResponse($response);
    }

    /**
     * @throws OAuthException
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new OAuthException(
                sprintf('OAuth HTTP request failed: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    private function createBaseRequest(string $endpoint): RequestInterface
    {
        $parsedUrl = parse_url($endpoint);
        $scheme = $parsedUrl['scheme'] ?? $this->uri->getScheme();
        $host = $parsedUrl['host'] ?? $this->uri->getHost();
        $port = $parsedUrl['port'] ?? $this->uri->getPort();

        $uri = $this->uri
            ->withScheme($scheme)
            ->withHost($host);

        if ($port !== null) {
            $uri = $uri->withPort($port);
        }

        return $this->psrFactories->getRequestFactory()->createRequest('GET', $uri);
    }

    /**
     * @return array<string, mixed>
     * @throws OAuthException
     */
    private function getProviderConfig(SocialProvider $provider): array
    {
        $config = $this->config[$provider->value] ?? null;

        if ($config === null) {
            throw new OAuthException(
                sprintf('OAuth configuration not found for provider: %s', $provider->value),
            );
        }

        return $config;
    }
}
