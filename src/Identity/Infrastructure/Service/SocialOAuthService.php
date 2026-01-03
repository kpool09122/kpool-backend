<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Http\Client\OAuthHttpClient;
use Psr\Log\LoggerInterface;
use Source\Identity\Domain\Exception\SocialOAuthException;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;

readonly class SocialOAuthService implements SocialOAuthServiceInterface
{
    /**
     * @param array<string, array<string, mixed>> $config
     */
    public function __construct(
        private OAuthHttpClient $oAuthHttpClient,
        private array $config,
        private LoggerInterface $logger,
    ) {
    }

    public function buildRedirectUrl(SocialProvider $provider, OAuthState $state): string
    {
        $providerConfig = $this->getProviderConfig($provider);

        /** @var string[] $scopes */
        $scopes = $providerConfig['scopes'];

        $params = [
            'client_id' => $providerConfig['client_id'],
            'redirect_uri' => $providerConfig['redirect_uri'],
            'response_type' => 'code',
            'state' => (string) $state,
            'scope' => implode(' ', $scopes),
        ];

        /** @var string $authorizationEndpoint */
        $authorizationEndpoint = $providerConfig['authorization_endpoint'];

        return $authorizationEndpoint . '?' . http_build_query($params);
    }

    /**
     * @throws SocialOAuthException
     */
    public function fetchProfile(SocialProvider $provider, OAuthCode $code): SocialProfile
    {
        $this->logger->info('Fetching social profile', [
            'provider' => $provider->value,
        ]);

        $tokenResponse = $this->oAuthHttpClient->exchangeCodeForToken($provider, (string) $code);
        $userInfo = $this->oAuthHttpClient->fetchUserInfo($provider, $tokenResponse['access_token']);

        return $this->mapToSocialProfile($provider, $userInfo, $tokenResponse['id_token']);
    }

    /**
     * @param array<string, mixed> $userInfo
     * @throws SocialOAuthException
     */
    private function mapToSocialProfile(SocialProvider $provider, array $userInfo, ?string $idToken): SocialProfile
    {
        return match ($provider) {
            SocialProvider::GOOGLE => $this->mapGoogleProfile($userInfo),
            SocialProvider::LINE => $this->mapLineProfile($userInfo, $idToken),
            SocialProvider::KAKAO => $this->mapKakaoProfile($userInfo),
        };
    }

    /**
     * @param array<string, mixed> $userInfo
     */
    private function mapGoogleProfile(array $userInfo): SocialProfile
    {
        /** @var string|int $id */
        $id = $userInfo['id'];

        /** @var string $email */
        $email = $userInfo['email'];

        /** @var string|null $name */
        $name = $userInfo['name'] ?? null;

        /** @var string|null $picture */
        $picture = $userInfo['picture'] ?? null;

        return new SocialProfile(
            provider: SocialProvider::GOOGLE,
            providerUserId: (string) $id,
            email: new Email($email),
            name: $name,
            avatarUrl: $picture,
        );
    }

    /**
     * @param array<string, mixed> $userInfo
     * @throws SocialOAuthException
     */
    private function mapLineProfile(array $userInfo, ?string $idToken): SocialProfile
    {
        $email = null;

        if ($idToken !== null) {
            $email = $this->extractEmailFromIdToken($idToken);
        }

        if ($email === null) {
            throw new SocialOAuthException('Email not available from LINE. Please grant email permission.');
        }

        /** @var string $userId */
        $userId = $userInfo['userId'];

        /** @var string|null $displayName */
        $displayName = $userInfo['displayName'] ?? null;

        /** @var string|null $pictureUrl */
        $pictureUrl = $userInfo['pictureUrl'] ?? null;

        return new SocialProfile(
            provider: SocialProvider::LINE,
            providerUserId: $userId,
            email: new Email($email),
            name: $displayName,
            avatarUrl: $pictureUrl,
        );
    }

    /**
     * @param array<string, mixed> $userInfo
     * @throws SocialOAuthException
     */
    private function mapKakaoProfile(array $userInfo): SocialProfile
    {
        /** @var array<string, mixed> $kakaoAccount */
        $kakaoAccount = $userInfo['kakao_account'] ?? [];

        /** @var array<string, mixed> $profile */
        $profile = $kakaoAccount['profile'] ?? [];

        /** @var string|null $email */
        $email = $kakaoAccount['email'] ?? null;

        if ($email === null) {
            throw new SocialOAuthException('Email not available from Kakao. Please grant email permission.');
        }

        /** @var string|int $id */
        $id = $userInfo['id'];

        /** @var string|null $nickname */
        $nickname = $profile['nickname'] ?? null;

        /** @var string|null $profileImageUrl */
        $profileImageUrl = $profile['profile_image_url'] ?? null;

        return new SocialProfile(
            provider: SocialProvider::KAKAO,
            providerUserId: (string) $id,
            email: new Email($email),
            name: $nickname,
            avatarUrl: $profileImageUrl,
        );
    }

    private function extractEmailFromIdToken(string $idToken): ?string
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            return null;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode(base64_decode($parts[1]), true);

        if ($payload === null) {
            return null;
        }

        /** @var string|null $email */
        $email = $payload['email'] ?? null;

        return $email;
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
