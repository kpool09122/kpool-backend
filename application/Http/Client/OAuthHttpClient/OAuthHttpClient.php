<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient;

use Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken\ExchangeCodeForTokenRequest;
use Application\Http\Client\OAuthHttpClient\ExchangeCodeForToken\ExchangeCodeForTokenResponse;
use Application\Http\Client\OAuthHttpClient\FetchUserInfo\FetchUserInfoRequest;
use Application\Http\Client\OAuthHttpClient\FetchUserInfo\FetchUserInfoResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Source\Identity\Domain\Exception\SocialOAuthException;
use Source\Identity\Domain\ValueObject\SocialProvider;

class OAuthHttpClient
{
    /**
     * @param array<string, array<string, mixed>> $config
     */
    public function __construct(
        private readonly array $config,
    ) {
    }

    /**
     * @throws SocialOAuthException
     * @throws ConnectionException
     */
    public function exchangeCodeForToken(ExchangeCodeForTokenRequest $request): ExchangeCodeForTokenResponse
    {
        $providerConfig = $this->getProviderConfig($request->provider());

        /** @var string $tokenEndpoint */
        $tokenEndpoint = $providerConfig['token_endpoint'];

        $response = Http::asForm()->post($tokenEndpoint, [
            'grant_type' => 'authorization_code',
            'client_id' => $providerConfig['client_id'],
            'client_secret' => $providerConfig['client_secret'],
            'redirect_uri' => $providerConfig['redirect_uri'],
            'code' => $request->code(),
        ]);

        if ($response->failed()) {
            throw new SocialOAuthException(
                sprintf('Failed to exchange code for token: %s', $response->body()),
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        /** @var string $accessToken */
        $accessToken = $data['access_token'];

        /** @var string|null $idToken */
        $idToken = $data['id_token'] ?? null;

        return new ExchangeCodeForTokenResponse(
            accessToken: $accessToken,
            idToken: $idToken,
        );
    }

    /**
     * @throws SocialOAuthException
     * @throws ConnectionException
     */
    public function fetchUserInfo(FetchUserInfoRequest $request): FetchUserInfoResponse
    {
        $providerConfig = $this->getProviderConfig($request->provider());

        /** @var string $userinfoEndpoint */
        $userinfoEndpoint = $providerConfig['userinfo_endpoint'];

        $response = Http::withToken($request->accessToken())->get($userinfoEndpoint);

        if ($response->failed()) {
            throw new SocialOAuthException(
                sprintf('Failed to fetch user info: %s', $response->body()),
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return new FetchUserInfoResponse(
            data: $data,
        );
    }

    /**
     * @return array<string, mixed>
     * @throws SocialOAuthException
     */
    private function getProviderConfig(SocialProvider $provider): array
    {
        $config = $this->config[$provider->value] ?? null;

        if ($config === null) {
            throw new SocialOAuthException(
                sprintf('OAuth configuration not found for provider: %s', $provider->value),
            );
        }

        return $config;
    }
}
