<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Identity\Identity;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Bridge\ClientRepository as PassportClientRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Exception\OAuthServerException;
use Mockery;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Factory\AuthorizationConsentFactoryInterface;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Identity\Infrastructure\Service\McpAuthorizationService;
use Tests\Helper\CreateAuthorizationConsent;
use Tests\TestCase;

class McpAuthorizationServiceTest extends TestCase
{
    private const string RESOURCE = 'https://mcp.example.test/mcp';
    private const string REDIRECT = 'https://chatgpt.example.test/callback';
    private const string VERIFIER = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';

    public function testFoundationDoesNotExposePublicConsentBypassRoutes(): void
    {
        $this->app()->make(McpAuthorizationService::class);
        $routes = [];
        foreach ($this->app()['router']->getRoutes()->getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'oauth/')) {
                $routes[] = $route->uri();
            }
        }
        $this->assertSame([], $routes);
    }

    #[Group('useDb')]
    public function testRealCodeExchangeAndRefreshPreserveConsentAndRotateTokens(): void
    {
        $consent = $this->fixture();
        $verifier = $this->app()->make(AuthorizationTokenVerifierInterface::class);
        $code = $this->code($consent);
        $tokens = $this->exchange($consent, $code);
        $validated = $verifier->verify($tokens['access_token']);
        $this->assertEquals($consent->identifier(), $validated->consent->identifier());
        $this->assertSame((string) $consent->identityIdentifier(), (string) $validated->consent->identityIdentifier());
        $this->assertEquals($consent->clientIdentifier(), $validated->consent->clientIdentifier());
        $this->assertSame($consent->scopes(), $validated->scopes);
        $this->assertSame(self::RESOURCE, $validated->consent->resource());
        $refreshed = $this->refresh($consent, $tokens['refresh_token']);
        $new = $verifier->verify($refreshed['access_token']);
        $this->assertEquals($consent->identifier(), $new->consent->identifier());
        $this->assertNotSame($validated->tokenIdentifier, $new->tokenIdentifier);
        $this->assertTrue(Passport::token()->newQuery()->findOrFail($validated->tokenIdentifier)->revoked);
        $this->assertSame(3600, $tokens['expires_in']);
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidAuthorizationParameters(): array
    {
        return [
            'unregistered redirect' => [['redirect_uri' => 'https://evil.example/callback']],
            'redirect not exact' => [['redirect_uri' => self::REDIRECT . '/']],
            'missing resource' => [['resource' => '']],
            'other resource' => [['resource' => 'https://other.example/mcp']],
            'unknown scope' => [['scope' => 'unknown']],
            'not consented scope' => [['scope' => 'wiki-images:write']],
            'plain PKCE' => [['code_challenge_method' => 'plain']],
            'missing challenge' => [['code_challenge' => '']],
        ];
    }

    /** @param array<string, string> $overrides */
    #[Group('useDb')]
    #[DataProvider('invalidAuthorizationParameters')]
    public function testAuthorizationRejectsInvalidParameters(array $overrides): void
    {
        $consent = $this->fixture();
        $this->expectException(OAuthServerException::class);
        $this->code($consent, $overrides);
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidExchangeParameters(): array
    {
        return [
            'PKCE mismatch' => [['code_verifier' => str_repeat('z', 64)]],
            'tampered code' => [['code' => 'tampered']],
            'wrong redirect' => [['redirect_uri' => 'https://evil.example/callback']],
            'missing resource' => [['resource' => '']],
            'wrong resource' => [['resource' => 'https://other.example/mcp']],
            'scope escalation' => [['scope' => 'wiki-images:write']],
            'consent swap' => [['authorization_consent_id' => 'other']],
            'legacy consent swap' => [['authorization_connection_id' => 'other']],
            'identity swap' => [['identity_id' => 'other']],
            'account expansion' => [['account_id' => 'other']],
        ];
    }

    /** @param array<string, string> $overrides */
    #[Group('useDb')]
    #[DataProvider('invalidExchangeParameters')]
    public function testCodeExchangeRejectsInvalidParametersWithoutConsumingCode(array $overrides): void
    {
        $consent = $this->fixture();
        $code = $this->code($consent);

        try {
            $this->exchange($consent, $code, $overrides);
            $this->fail('Invalid exchange was accepted.');
        } catch (OAuthServerException) {
            $tokens = $this->exchange($consent, $code);
            $this->assertEquals($consent->identifier(), $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token'])->consent->identifier());
        }
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidRefreshParameters(): array
    {
        return [
            'scope expansion' => [['scope' => 'wiki-images:write']],
            'unknown scope' => [['scope' => 'unknown']],
            'resource change' => [['resource' => 'https://other.example/mcp']],
            'empty resource' => [['resource' => '']],
            'consent swap' => [['connection_id' => 'other']],
            'account expansion' => [['account_id' => 'other']],
        ];
    }

    /** @param array<string, string> $overrides */
    #[Group('useDb')]
    #[DataProvider('invalidRefreshParameters')]
    public function testRefreshRejectsExpansionWithoutConsumingRefreshToken(array $overrides): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));

        try {
            $this->refresh($consent, $tokens['refresh_token'], $overrides);
            $this->fail('Invalid refresh was accepted.');
        } catch (OAuthServerException) {
            $refreshed = $this->refresh($consent, $tokens['refresh_token']);
            $this->assertEquals($consent->identifier(), $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($refreshed['access_token'])->consent->identifier());
        }
    }

    #[Group('useDb')]
    public function testRefreshCanNarrowButCannotRegainRemovedScopes(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        $narrowed = $this->refresh($consent, $tokens['refresh_token'], ['scope' => 'wiki:read', 'resource' => self::RESOURCE]);
        $this->assertSame([AuthorizationScope::WikiRead], $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($narrowed['access_token'])->scopes);
        $this->expectException(OAuthServerException::class);
        $this->refresh($consent, $narrowed['refresh_token'], ['scope' => 'wiki:read wiki-drafts:read']);
    }

    #[Group('useDb')]
    public function testAuthorizationCodeCannotBeReused(): void
    {
        $consent = $this->fixture();
        $code = $this->code($consent);
        $this->exchange($consent, $code);
        $this->expectException(OAuthServerException::class);
        $this->exchange($consent, $code);
    }

    #[Group('useDb')]
    public function testOldRefreshCannotBeReused(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        $this->refresh($consent, $tokens['refresh_token']);
        $this->expectException(OAuthServerException::class);
        $this->refresh($consent, $tokens['refresh_token']);
    }

    /** @return array<string, array{string}> */
    public static function revokedStages(): array
    {
        return ['authorize' => ['authorize'], 'exchange' => ['exchange'], 'refresh' => ['refresh'], 'verify' => ['verify']];
    }

    #[Group('useDb')]
    #[DataProvider('revokedStages')]
    public function testRevokedConsentIsRejectedAtEveryStage(string $stage): void
    {
        $consent = $this->fixture();
        $code = $stage === 'authorize' ? '' : $this->code($consent);
        $tokens = in_array($stage, ['refresh', 'verify'], true) ? $this->exchange($consent, $code) : ['access_token' => '', 'refresh_token' => '', 'expires_in' => 0];
        $consent->revoke(new DateTimeImmutable());
        $this->app()->make(AuthorizationConsentRepositoryInterface::class)->save($consent);
        $this->expectException($stage === 'verify' ? InvalidAuthorizationTokenException::class : OAuthServerException::class);
        match ($stage) {
            'authorize' => $this->code($consent),
            'exchange' => $this->exchange($consent, $code),
            'refresh' => $this->refresh($consent, $tokens['refresh_token']),
            'verify' => $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token']),
            default => $this->fail('Unknown stage'),
        };
    }

    #[Group('useDb')]
    public function testDistinctConsentsForSameIdentityAndClientArePreserved(): void
    {
        $first = $this->fixture();
        $second = $this->app()->make(AuthorizationConsentFactoryInterface::class)->create(
            $first->identityIdentifier(),
            $first->clientIdentifier(),
            [AuthorizationScope::WikiRead],
            $first->originalAccountIdentifier(),
            $first->originalPrincipalIdentifier(),
            $first->resource(),
            new DateTimeImmutable()
        );
        $this->app()->make(AuthorizationConsentRepositoryInterface::class)->save($second);
        $verifier = $this->app()->make(AuthorizationTokenVerifierInterface::class);
        $firstTokens = $this->exchange($first, $this->code($first));
        $secondTokens = $this->exchange($second, $this->code($second));
        $this->assertEquals($first->identifier(), $verifier->verify($firstTokens['access_token'])->consent->identifier());
        $this->assertEquals($second->identifier(), $verifier->verify($secondTokens['access_token'])->consent->identifier());
        $this->assertSame([AuthorizationScope::WikiRead], $verifier->verify($secondTokens['access_token'])->scopes);
        $first->revoke(new DateTimeImmutable());
        $this->app()->make(AuthorizationConsentRepositoryInterface::class)->save($first);
        $this->assertEquals($second->identifier(), $verifier->verify($secondTokens['access_token'])->consent->identifier());
    }

    #[Group('useDb')]
    public function testAnotherRegisteredClientCannotExchangeTheCode(): void
    {
        $consent = $this->fixture();
        $other = $this->app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('Other', [self::REDIRECT], false);
        $code = $this->code($consent);
        $this->expectException(OAuthServerException::class);
        $this->exchange($consent, $code, ['client_id' => $other->id]);
    }

    #[Group('useDb')]
    public function testAnotherRegisteredClientCannotRefreshTheToken(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        $other = $this->app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('Other', [self::REDIRECT], false);
        $this->expectException(OAuthServerException::class);
        $this->refresh($consent, $tokens['refresh_token'], ['client_id' => $other->id]);
    }

    #[Group('useDb')]
    public function testRevokedRefreshTokenIsRejected(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        Passport::refreshToken()->newQuery()->update(['revoked' => true]);
        $this->expectException(OAuthServerException::class);
        $this->refresh($consent, $tokens['refresh_token']);
    }

    #[Group('useDb')]
    public function testExpiredRefreshTokenIsRejected(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        Passport::refreshToken()->newQuery()->update(['expires_at' => now()->subSecond()]);
        $this->expectException(OAuthServerException::class);
        $this->refresh($consent, $tokens['refresh_token']);
    }

    #[Group('useDb')]
    public function testExpiredCodeIsRejected(): void
    {
        $consent = $this->fixture();
        $code = $this->code($consent);
        Passport::authCode()->newQuery()->update(['expires_at' => now()->subSecond()]);
        $this->expectException(OAuthServerException::class);
        $this->exchange($consent, $code);
    }

    #[Group('useDb')]
    public function testExpiredAccessTokenIsRejected(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        Passport::token()->newQuery()->update(['expires_at' => now()->subSecond()]);
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testSignatureTamperingIsRejected(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        $parts = explode('.', $tokens['access_token']);
        $parts[1] = rtrim(strtr(base64_encode('{"sub":"other"}'), '+/', '-_'), '=');
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify(implode('.', $parts));
    }

    #[Group('useDb')]
    public function testValidSignatureWithWrongIssuerOrAudienceIsRejected(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        config()->set('mcp_oauth.issuer', 'https://other.example');
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testConsentPortIssuesCodeWithARequestedSubset(): void
    {
        $consent = $this->fixture();
        $service = $this->app()->make(AuthorizationConsentServiceInterface::class);
        $redirect = $service->issueCode($consent->identifier(), new AuthorizationCodeParameters(
            $consent->clientIdentifier(),
            self::REDIRECT,
            [AuthorizationScope::WikiRead],
            self::RESOURCE,
            rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
            ' opaque state & value ',
        ));
        parse_str((string) parse_url($redirect, PHP_URL_QUERY), $parameters);
        $this->assertSame(' opaque state & value ', $parameters['state']);
        $this->assertIsString($parameters['code']);
        $tokens = $this->exchange($consent, $parameters['code']);
        $this->assertSame([AuthorizationScope::WikiRead], $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token'])->scopes);
    }

    #[Group('useDb')]
    public function testConsentPortConvertsInvalidConsentWithoutIssuingCode(): void
    {
        $consent = $this->fixture();
        $this->expectException(InvalidAuthorizationConsentException::class);
        $this->app()->make(AuthorizationConsentServiceInterface::class)->issueCode(
            $consent->identifier(),
            new AuthorizationCodeParameters($consent->clientIdentifier(), self::REDIRECT, [AuthorizationScope::ImageWrite], self::RESOURCE, str_repeat('a', 43), 'state')
        );
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidSignedClaims(): array
    {
        return [
            'issuer' => ['iss', 'https://other.example'],
            'audience is client' => ['aud', ['client']],
            'multiple audience' => ['aud', [self::RESOURCE, 'https://other.example']],
            'subject' => ['sub', 'other'],
            'client' => ['client_id', 'other'],
            'scopes' => ['scopes', ['wiki-images:write']],
            'expired JWT' => ['exp', 1],
        ];
    }

    #[Group('useDb')]
    #[DataProvider('invalidSignedClaims')]
    public function testEvenValidSignaturesCannotChangeTokenBinding(string $claim, mixed $value): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        $encoded = explode('.', $tokens['access_token'])[1];
        $payload = json_decode((string) base64_decode(strtr($encoded, '-_', '+/'), true), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($payload);
        $payload[$claim] = $value;
        $key = config()->string('passport.private_key');
        $signed = JWT::encode($payload, $key, 'RS256');
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($signed);
    }

    /** @return array<string, array{string}> */
    public static function unsupportedGrants(): array
    {
        return ['password' => ['password'], 'implicit' => ['implicit'], 'client credentials' => ['client_credentials'], 'personal access' => ['personal_access']];
    }

    #[DataProvider('unsupportedGrants')]
    public function testOtherTokenGrantTypesAreNotEnabled(string $grant): void
    {
        $this->expectException(OAuthServerException::class);
        $this->tokenRequest(['grant_type' => $grant]);
    }

    #[Group('useDb')]
    public function testRevokedClientCannotContinueUsingExistingAccessToken(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        Passport::client()->newQuery()->whereKey((string) $consent->clientIdentifier())->update(['revoked' => true]);
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testPersistedAccessRevocationRejectsVerification(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        Passport::token()->newQuery()->update(['revoked' => true]);
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testIdentityIsMappedToPassportWithoutReplacingWebSessionGuard(): void
    {
        $consent = $this->fixture();
        $identity = Identity::query()->findOrFail((string) $consent->identityIdentifier());
        $this->assertSame('users', $identity->getProviderName());
        $this->assertSame('session', config('auth.guards.web.driver'));
        $tokens = $this->exchange($consent, $this->code($consent));
        $this->assertSame(1, $identity->tokens()->count());
        $this->assertFalse($identity->tokenCan('wiki:read'));
        $this->assertEquals($consent->identifier(), $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token'])->consent->identifier());
    }

    #[Group('useDb')]
    public function testLoopbackRedirectPortMustAlsoMatchExactly(): void
    {
        $first = $this->fixture();
        $client = $this->app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('Loopback', ['http://127.0.0.1:4000/callback'], false);
        $consent = $this->app()->make(AuthorizationConsentFactoryInterface::class)->create(
            $first->identityIdentifier(),
            new OAuthClientIdentifier($client->id),
            $first->scopes(),
            $first->originalAccountIdentifier(),
            $first->originalPrincipalIdentifier(),
            $first->resource(),
            new DateTimeImmutable()
        );
        $this->app()->make(AuthorizationConsentRepositoryInterface::class)->save($consent);
        $this->expectException(OAuthServerException::class);
        $this->code($consent, ['redirect_uri' => 'http://127.0.0.1:5000/callback']);
    }

    #[Group('useDb')]
    public function testCallerTransactionRollsBackRefreshRotationAfterARealWriteFails(): void
    {
        $consent = $this->fixture();
        $tokens = $this->exchange($consent, $this->code($consent));
        $dispatcher = Mockery::mock(Dispatcher::class);
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $failure = new RuntimeException('Simulated post-insert failure');
        $dispatcher->shouldReceive('dispatch')->once()->andThrow($failure);
        $service = new McpAuthorizationService(
            $this->app()->make(AuthorizationConsentRepositoryInterface::class),
            $this->app()->make(PassportClientRepository::class),
            $this->app()->make(ClientRepository::class),
            $dispatcher,
            $this->app()->make(Encrypter::class),
            $this->app()->make(InternalApiTokenServiceInterface::class),
            $this->app()->make(LoggerInterface::class),
        );

        try {
            DB::transaction(fn () => $service->exchange((new ServerRequest('POST', '/oauth/token'))->withParsedBody([
                'grant_type' => 'refresh_token', 'client_id' => (string) $consent->clientIdentifier(), 'refresh_token' => $tokens['refresh_token'],
            ])));
            $this->fail('Expected the event boundary to fail after persistence.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertSame(1, Passport::token()->newQuery()->count());
        $this->assertSame(0, Passport::token()->newQuery()->where('revoked', true)->count());
        $this->assertSame(1, Passport::refreshToken()->newQuery()->count());
        $this->assertSame(0, Passport::refreshToken()->newQuery()->where('revoked', true)->count());
        $this->assertEquals($consent->identifier(), $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token'])->consent->identifier());
        $this->refresh($consent, $tokens['refresh_token']);
    }

    private function fixture(): AuthorizationConsent
    {
        return CreateAuthorizationConsent::create();
    }

    /** @param array<string, string> $overrides */
    private function code(AuthorizationConsent $consent, array $overrides = []): string
    {
        $query = array_replace([
            'client_id' => (string) $consent->clientIdentifier(), 'response_type' => 'code', 'redirect_uri' => self::REDIRECT,
            'resource' => self::RESOURCE, 'scope' => implode(' ', array_map(static fn (AuthorizationScope $scope): string => $scope->value, $consent->scopes())),
            'code_challenge_method' => 'S256', 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
            'state' => 'test-state',
        ], $overrides);
        $response = $this->app()->make(McpAuthorizationService::class)->authorize(
            $consent->identifier(),
            (new ServerRequest('GET', '/oauth/authorize'))->withQueryParams($query)
        );
        parse_str((string) parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $parameters);
        $this->assertSame('test-state', $parameters['state']);
        $this->assertIsString($parameters['code']);

        return $parameters['code'];
    }

    /** @param array<string, string> $overrides
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    private function exchange(AuthorizationConsent $consent, string $code, array $overrides = []): array
    {
        return $this->tokenRequest(array_replace([
            'client_id' => (string) $consent->clientIdentifier(), 'grant_type' => 'authorization_code',
            'redirect_uri' => self::REDIRECT, 'code' => $code, 'code_verifier' => self::VERIFIER, 'resource' => self::RESOURCE,
        ], $overrides));
    }

    /** @param array<string, string> $overrides
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    private function refresh(AuthorizationConsent $consent, string $refresh, array $overrides = []): array
    {
        return $this->tokenRequest(array_replace([
            'client_id' => (string) $consent->clientIdentifier(), 'grant_type' => 'refresh_token', 'refresh_token' => $refresh,
        ], $overrides));
    }

    /** @param array<string, string> $parameters
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    private function tokenRequest(array $parameters): array
    {
        $response = $this->app()->make(McpAuthorizationService::class)->exchange(
            (new ServerRequest('POST', '/oauth/token'))->withParsedBody($parameters)
        );
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertIsString($data['access_token']);
        $this->assertIsString($data['refresh_token']);
        $this->assertIsInt($data['expires_in']);

        return ['access_token' => $data['access_token'], 'refresh_token' => $data['refresh_token'], 'expires_in' => $data['expires_in']];
    }
}
