<?php

declare(strict_types=1);

namespace Application\Http\Client;

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
     * @return array{access_token: string, id_token: string|null}
     * @throws SocialOAuthException
     */
    public function exchangeCodeForToken(SocialProvider $provider, string $code): array
    {
        $providerConfig = $this->getProviderConfig($provider);

        /** @var string $tokenEndpoint */
        $tokenEndpoint = $providerConfig['token_endpoint'];

        $response = Http::asForm()->post($tokenEndpoint, [
            'grant_type' => 'authorization_code',
            'client_id' => $providerConfig['client_id'],
            'client_secret' => $providerConfig['client_secret'],
            'redirect_uri' => $providerConfig['redirect_uri'],
            'code' => $code,
        ]);

        if ($response->failed()) {
            /** @var string|null $errorCode */
            $errorCode = $response->json('error');

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

        return [
            'access_token' => $accessToken,
            'id_token' => $idToken,
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws SocialOAuthException
     */
    public function fetchUserInfo(SocialProvider $provider, string $accessToken): array
    {
        $providerConfig = $this->getProviderConfig($provider);

        /** @var string $userinfoEndpoint */
        $userinfoEndpoint = $providerConfig['userinfo_endpoint'];

        $response = Http::withToken($accessToken)->get($userinfoEndpoint);

        if ($response->failed()) {
            /** @var string|null $errorCode */
            $errorCode = $response->json('error');

            throw new SocialOAuthException(
                sprintf('Failed to fetch user info: %s', $response->body()),
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return $data;
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
