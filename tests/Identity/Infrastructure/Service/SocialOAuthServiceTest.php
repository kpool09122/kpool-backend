<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Http\Client\OAuthHttpClient;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Source\Identity\Domain\Exception\SocialOAuthException;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Infrastructure\Service\SocialOAuthService;
use Tests\TestCase;

class SocialOAuthServiceTest extends TestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function getConfig(): array
    {
        return [
            'google' => [
                'client_id' => 'google-client-id',
                'client_secret' => 'google-client-secret',
                'redirect_uri' => 'https://example.com/callback/google',
                'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'scopes' => ['openid', 'email', 'profile'],
            ],
            'line' => [
                'client_id' => 'line-client-id',
                'client_secret' => 'line-client-secret',
                'redirect_uri' => 'https://example.com/callback/line',
                'authorization_endpoint' => 'https://access.line.me/oauth2/v2.1/authorize',
                'scopes' => ['openid', 'profile', 'email'],
            ],
            'kakao' => [
                'client_id' => 'kakao-client-id',
                'client_secret' => 'kakao-client-secret',
                'redirect_uri' => 'https://example.com/callback/kakao',
                'authorization_endpoint' => 'https://kauth.kakao.com/oauth/authorize',
                'scopes' => ['account_email', 'profile_nickname'],
            ],
        ];
    }

    /**
     * @return array<string, array{SocialProvider, string, string}>
     */
    public static function providerDataForBuildRedirectUrl(): array
    {
        return [
            'Google' => [
                SocialProvider::GOOGLE,
                'https://accounts.google.com/o/oauth2/v2/auth',
                'openid email profile',
            ],
            'LINE' => [
                SocialProvider::LINE,
                'https://access.line.me/oauth2/v2.1/authorize',
                'openid profile email',
            ],
            'Kakao' => [
                SocialProvider::KAKAO,
                'https://kauth.kakao.com/oauth/authorize',
                'account_email profile_nickname',
            ],
        ];
    }

    /**
     * 正常系: 各プロバイダーでリダイレクトURLが正しく構築されること.
     *
     * @param SocialProvider $provider
     * @param string $expectedEndpoint
     * @param string $expectedScope
     * @return void
     * @throws BindingResolutionException
     */
    #[DataProvider('providerDataForBuildRedirectUrl')]
    public function testBuildRedirectUrl(SocialProvider $provider, string $expectedEndpoint, string $expectedScope): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);
        $config = $this->getConfig();

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($config);

        $service = $this->app->make(SocialOAuthService::class);

        $state = new OAuthState('test-state-value', new DateTimeImmutable('+10 minutes'));

        $url = $service->buildRedirectUrl($provider, $state);

        $this->assertStringStartsWith($expectedEndpoint, $url);
        $this->assertStringContainsString('client_id=' . $config[$provider->value]['client_id'], $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode($config[$provider->value]['redirect_uri']), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=test-state-value', $url);
        $this->assertStringContainsString('scope=' . urlencode($expectedScope), $url);
    }

    /**
     * 正常系: Googleプロバイダーでプロファイルをfetchできること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFetchProfileForGoogle(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('google-auth-code');

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->with(SocialProvider::GOOGLE, 'google-auth-code')
            ->andReturn([
                'access_token' => 'google-access-token',
                'id_token' => null,
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->with(SocialProvider::GOOGLE, 'google-access-token')
            ->andReturn([
                'id' => '123456789',
                'email' => 'user@gmail.com',
                'name' => 'Test User',
                'picture' => 'https://example.com/avatar.jpg',
            ]);

        $profile = $service->fetchProfile(SocialProvider::GOOGLE, $code);

        $this->assertSame(SocialProvider::GOOGLE, $profile->provider());
        $this->assertSame('123456789', $profile->providerUserId());
        $this->assertSame('user@gmail.com', (string) $profile->email());
        $this->assertSame('Test User', $profile->name());
        $this->assertSame('https://example.com/avatar.jpg', $profile->avatarUrl());
    }

    /**
     * 正常系: LINEプロバイダーでプロファイルをfetchできること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testFetchProfileForLine(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('line-auth-code');
        $idToken = $this->createIdToken(['email' => 'user@line.me']);

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->with(SocialProvider::LINE, 'line-auth-code')
            ->andReturn([
                'access_token' => 'line-access-token',
                'id_token' => $idToken,
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->with(SocialProvider::LINE, 'line-access-token')
            ->andReturn([
                'userId' => 'U1234567890abcdef',
                'displayName' => 'LINE User',
                'pictureUrl' => 'https://profile.line-scdn.net/avatar.jpg',
            ]);

        $profile = $service->fetchProfile(SocialProvider::LINE, $code);

        $this->assertSame(SocialProvider::LINE, $profile->provider());
        $this->assertSame('U1234567890abcdef', $profile->providerUserId());
        $this->assertSame('user@line.me', (string) $profile->email());
        $this->assertSame('LINE User', $profile->name());
        $this->assertSame('https://profile.line-scdn.net/avatar.jpg', $profile->avatarUrl());
    }

    /**
     * 異常系: LINEでidTokenにemailがない場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFetchProfileForLineThrowsExceptionWhenEmailNotAvailable(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('line-auth-code');
        $idToken = $this->createIdToken([]);

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->andReturn([
                'access_token' => 'line-access-token',
                'id_token' => $idToken,
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->andReturn([
                'userId' => 'U1234567890abcdef',
                'displayName' => 'LINE User',
            ]);

        $this->expectException(SocialOAuthException::class);
        $this->expectExceptionMessage('Email not available from LINE. Please grant email permission.');

        $service->fetchProfile(SocialProvider::LINE, $code);
    }

    /**
     * 異常系: LINEでidTokenがnullの場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFetchProfileForLineThrowsExceptionWhenIdTokenIsNull(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('line-auth-code');

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->andReturn([
                'access_token' => 'line-access-token',
                'id_token' => null,
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->andReturn([
                'userId' => 'U1234567890abcdef',
                'displayName' => 'LINE User',
            ]);

        $this->expectException(SocialOAuthException::class);
        $this->expectExceptionMessage('Email not available from LINE. Please grant email permission.');

        $service->fetchProfile(SocialProvider::LINE, $code);
    }

    /**
     * 異常系: LINEでidTokenが不正な形式の場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFetchProfileForLineThrowsExceptionWhenIdTokenIsInvalidFormat(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('line-auth-code');

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->andReturn([
                'access_token' => 'line-access-token',
                'id_token' => 'invalid-token-without-dots',
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->andReturn([
                'userId' => 'U1234567890abcdef',
                'displayName' => 'LINE User',
            ]);

        $this->expectException(SocialOAuthException::class);
        $this->expectExceptionMessage('Email not available from LINE. Please grant email permission.');

        $service->fetchProfile(SocialProvider::LINE, $code);
    }

    /**
     * 異常系: LINEでidTokenのpayloadが不正なJSONの場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFetchProfileForLineThrowsExceptionWhenIdTokenPayloadIsInvalidJson(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('line-auth-code');

        $header = base64_encode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $invalidPayload = base64_encode('not-valid-json{{{');
        $signature = base64_encode('test-signature');
        $invalidIdToken = $header . '.' . $invalidPayload . '.' . $signature;

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->andReturn([
                'access_token' => 'line-access-token',
                'id_token' => $invalidIdToken,
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->andReturn([
                'userId' => 'U1234567890abcdef',
                'displayName' => 'LINE User',
            ]);

        $this->expectException(SocialOAuthException::class);
        $this->expectExceptionMessage('Email not available from LINE. Please grant email permission.');

        $service->fetchProfile(SocialProvider::LINE, $code);
    }

    /**
     * 正常系: Kakaoプロバイダーでプロファイルをfetchできること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFetchProfileForKakao(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('kakao-auth-code');

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->with(SocialProvider::KAKAO, 'kakao-auth-code')
            ->andReturn([
                'access_token' => 'kakao-access-token',
                'id_token' => null,
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->with(SocialProvider::KAKAO, 'kakao-access-token')
            ->andReturn([
                'id' => 9876543210,
                'kakao_account' => [
                    'email' => 'user@kakao.com',
                    'profile' => [
                        'nickname' => 'Kakao User',
                        'profile_image_url' => 'https://k.kakaocdn.net/avatar.jpg',
                    ],
                ],
            ]);

        $profile = $service->fetchProfile(SocialProvider::KAKAO, $code);

        $this->assertSame(SocialProvider::KAKAO, $profile->provider());
        $this->assertSame('9876543210', $profile->providerUserId());
        $this->assertSame('user@kakao.com', (string) $profile->email());
        $this->assertSame('Kakao User', $profile->name());
        $this->assertSame('https://k.kakaocdn.net/avatar.jpg', $profile->avatarUrl());
    }

    /**
     * 異常系: Kakaoでemailがない場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testFetchProfileForKakaoThrowsExceptionWhenEmailNotAvailable(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give($this->getConfig());

        $service = $this->app->make(SocialOAuthService::class);

        $code = new OAuthCode('kakao-auth-code');

        $oAuthHttpClient
            ->shouldReceive('exchangeCodeForToken')
            ->once()
            ->andReturn([
                'access_token' => 'kakao-access-token',
                'id_token' => null,
            ]);

        $oAuthHttpClient
            ->shouldReceive('fetchUserInfo')
            ->once()
            ->andReturn([
                'id' => 9876543210,
                'kakao_account' => [
                    'profile' => [
                        'nickname' => 'Kakao User',
                    ],
                ],
            ]);

        $this->expectException(SocialOAuthException::class);
        $this->expectExceptionMessage('Email not available from Kakao. Please grant email permission.');

        $service->fetchProfile(SocialProvider::KAKAO, $code);
    }

    /**
     * 異常系: 設定がないプロバイダーの場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testBuildRedirectUrlThrowsExceptionWhenConfigNotFound(): void
    {
        $oAuthHttpClient = Mockery::mock(OAuthHttpClient::class);

        $this->app->instance(OAuthHttpClient::class, $oAuthHttpClient);
        $this->app->instance(LoggerInterface::class, new NullLogger());
        $this->app->when(SocialOAuthService::class)
            ->needs('$config')
            ->give([]);

        $service = $this->app->make(SocialOAuthService::class);

        $state = new OAuthState('test-state', new DateTimeImmutable('+10 minutes'));

        $this->expectException(SocialOAuthException::class);
        $this->expectExceptionMessage('OAuth configuration not found for provider: google');

        $service->buildRedirectUrl(SocialProvider::GOOGLE, $state);
    }

    /**
     * テスト用のJWT形式のidTokenを作成.
     *
     * @param array<string, mixed> $payload
     */
    private function createIdToken(array $payload): string
    {
        $header = base64_encode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payloadEncoded = base64_encode((string) json_encode($payload));
        $signature = base64_encode('test-signature');

        return $header . '.' . $payloadEncoded . '.' . $signature;
    }
}
