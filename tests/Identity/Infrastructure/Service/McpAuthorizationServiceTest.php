<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Identity\Identity;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Bridge\ClientRepository as PassportClientRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Exception\OAuthServerException;
use Mockery;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Infrastructure\Service\McpAuthorizationService;
use Tests\Helper\CreateAuthorizationConnection;
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
        $connection = $this->fixture();
        $service = $this->app()->make(McpAuthorizationService::class);
        $code = $this->code($connection);
        $tokens = $this->exchange($connection, $code);
        $validated = $service->verify($tokens['access_token']);
        $this->assertSame($connection->identifier, $validated->connection->identifier);
        $this->assertSame((string) $connection->identityIdentifier, (string) $validated->connection->identityIdentifier);
        $this->assertSame($connection->clientIdentifier, $validated->connection->clientIdentifier);
        $this->assertSame($connection->scopes, $validated->scopes);
        $this->assertSame(self::RESOURCE, $validated->connection->resource);
        $refreshed = $this->refresh($connection, $tokens['refresh_token']);
        $new = $service->verify($refreshed['access_token']);
        $this->assertSame($connection->identifier, $new->connection->identifier);
        $this->assertNotSame($validated->tokenIdentifier, $new->tokenIdentifier);
        $this->assertEquals($connection->targets, $new->connection->targets);
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
        $connection = $this->fixture();
        $this->expectException(OAuthServerException::class);
        $this->code($connection, $overrides);
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
            'connection swap' => [['authorization_connection_id' => 'other']],
            'identity swap' => [['identity_id' => 'other']],
            'account expansion' => [['account_id' => 'other']],
        ];
    }

    /** @param array<string, string> $overrides */
    #[Group('useDb')]
    #[DataProvider('invalidExchangeParameters')]
    public function testCodeExchangeRejectsInvalidParametersWithoutConsumingCode(array $overrides): void
    {
        $connection = $this->fixture();
        $code = $this->code($connection);

        try {
            $this->exchange($connection, $code, $overrides);
            $this->fail('Invalid exchange was accepted.');
        } catch (OAuthServerException) {
            $tokens = $this->exchange($connection, $code);
            $this->assertSame($connection->identifier, $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token'])->connection->identifier);
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
            'connection swap' => [['connection_id' => 'other']],
            'account expansion' => [['account_id' => 'other']],
        ];
    }

    /** @param array<string, string> $overrides */
    #[Group('useDb')]
    #[DataProvider('invalidRefreshParameters')]
    public function testRefreshRejectsExpansionWithoutConsumingRefreshToken(array $overrides): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));

        try {
            $this->refresh($connection, $tokens['refresh_token'], $overrides);
            $this->fail('Invalid refresh was accepted.');
        } catch (OAuthServerException) {
            $refreshed = $this->refresh($connection, $tokens['refresh_token']);
            $this->assertSame($connection->identifier, $this->app()->make(McpAuthorizationService::class)->verify($refreshed['access_token'])->connection->identifier);
        }
    }

    #[Group('useDb')]
    public function testRefreshCanNarrowButCannotRegainRemovedScopes(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        $narrowed = $this->refresh($connection, $tokens['refresh_token'], ['scope' => 'wiki:read', 'resource' => self::RESOURCE]);
        $this->assertSame(['wiki:read'], $this->app()->make(McpAuthorizationService::class)->verify($narrowed['access_token'])->scopes);
        $this->expectException(OAuthServerException::class);
        $this->refresh($connection, $narrowed['refresh_token'], ['scope' => 'wiki:read wiki-drafts:read']);
    }

    #[Group('useDb')]
    public function testAuthorizationCodeCannotBeReused(): void
    {
        $connection = $this->fixture();
        $code = $this->code($connection);
        $this->exchange($connection, $code);
        $this->expectException(OAuthServerException::class);
        $this->exchange($connection, $code);
    }

    #[Group('useDb')]
    public function testOldRefreshCannotBeReused(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        $this->refresh($connection, $tokens['refresh_token']);
        $this->expectException(OAuthServerException::class);
        $this->refresh($connection, $tokens['refresh_token']);
    }

    /** @return array<string, array{string}> */
    public static function revokedStages(): array
    {
        return ['authorize' => ['authorize'], 'exchange' => ['exchange'], 'refresh' => ['refresh'], 'verify' => ['verify']];
    }

    #[Group('useDb')]
    #[DataProvider('revokedStages')]
    public function testRevokedConnectionIsRejectedAtEveryStage(string $stage): void
    {
        $connection = $this->fixture();
        $code = $stage === 'authorize' ? '' : $this->code($connection);
        $tokens = in_array($stage, ['refresh', 'verify'], true) ? $this->exchange($connection, $code) : ['access_token' => '', 'refresh_token' => '', 'expires_in' => 0];
        $this->app()->make(AuthorizationConnectionRepositoryInterface::class)->revoke($connection->identifier);
        $this->expectException($stage === 'verify' ? InvalidAuthorizationTokenException::class : OAuthServerException::class);
        match ($stage) {
            'authorize' => $this->code($connection),
            'exchange' => $this->exchange($connection, $code),
            'refresh' => $this->refresh($connection, $tokens['refresh_token']),
            'verify' => $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token']),
            default => $this->fail('Unknown stage'),
        };
    }

    #[Group('useDb')]
    public function testDistinctConnectionsForSameIdentityAndClientArePreserved(): void
    {
        $first = $this->fixture();
        $second = new AuthorizationConnection(
            (string) Str::uuid7(),
            $first->identityIdentifier,
            $first->clientIdentifier,
            ['wiki:read'],
            $first->targets,
            $first->originalAccountIdentifier,
            $first->originalPrincipalIdentifier,
            $first->initialAccountIdentifier,
            $first->resource,
            new DateTimeImmutable()
        );
        $service = $this->app()->make(McpAuthorizationService::class);
        $service->createConnection($second);
        $firstTokens = $this->exchange($first, $this->code($first));
        $secondTokens = $this->exchange($second, $this->code($second));
        $this->assertSame($first->identifier, $service->verify($firstTokens['access_token'])->connection->identifier);
        $this->assertSame($second->identifier, $service->verify($secondTokens['access_token'])->connection->identifier);
        $this->assertSame(['wiki:read'], $service->verify($secondTokens['access_token'])->scopes);
        $this->app()->make(AuthorizationConnectionRepositoryInterface::class)->revoke($first->identifier);
        $this->assertSame($second->identifier, $service->verify($secondTokens['access_token'])->connection->identifier);
    }

    #[Group('useDb')]
    public function testAnotherRegisteredClientCannotExchangeTheCode(): void
    {
        $connection = $this->fixture();
        $other = $this->app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('Other', [self::REDIRECT], false);
        $code = $this->code($connection);
        $this->expectException(OAuthServerException::class);
        $this->exchange($connection, $code, ['client_id' => $other->id]);
    }

    #[Group('useDb')]
    public function testAnotherRegisteredClientCannotRefreshTheToken(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        $other = $this->app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('Other', [self::REDIRECT], false);
        $this->expectException(OAuthServerException::class);
        $this->refresh($connection, $tokens['refresh_token'], ['client_id' => $other->id]);
    }

    #[Group('useDb')]
    public function testRevokedRefreshTokenIsRejected(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        Passport::refreshToken()->newQuery()->update(['revoked' => true]);
        $this->expectException(OAuthServerException::class);
        $this->refresh($connection, $tokens['refresh_token']);
    }

    #[Group('useDb')]
    public function testExpiredRefreshTokenIsRejected(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        Passport::refreshToken()->newQuery()->update(['expires_at' => now()->subSecond()]);
        $this->expectException(OAuthServerException::class);
        $this->refresh($connection, $tokens['refresh_token']);
    }

    #[Group('useDb')]
    public function testExpiredCodeIsRejected(): void
    {
        $connection = $this->fixture();
        $code = $this->code($connection);
        Passport::authCode()->newQuery()->update(['expires_at' => now()->subSecond()]);
        $this->expectException(OAuthServerException::class);
        $this->exchange($connection, $code);
    }

    #[Group('useDb')]
    public function testExpiredAccessTokenIsRejected(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        Passport::token()->newQuery()->update(['expires_at' => now()->subSecond()]);
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testSignatureTamperingIsRejected(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        $parts = explode('.', $tokens['access_token']);
        $parts[1] = rtrim(strtr(base64_encode('{"sub":"other"}'), '+/', '-_'), '=');
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(McpAuthorizationService::class)->verify(implode('.', $parts));
    }

    #[Group('useDb')]
    public function testValidSignatureWithWrongIssuerOrAudienceIsRejected(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        config()->set('mcp_oauth.issuer', 'https://other.example');
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testConsentPortIssuesCodeWithARequestedSubset(): void
    {
        $connection = $this->fixture();
        $service = $this->app()->make(AuthorizationConsentServiceInterface::class);
        $redirect = $service->issueCode($connection->identifier, new AuthorizationCodeParameters(
            $connection->clientIdentifier,
            self::REDIRECT,
            ['wiki:read'],
            self::RESOURCE,
            rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
            'state',
        ));
        parse_str((string) parse_url($redirect, PHP_URL_QUERY), $parameters);
        $this->assertSame('state', $parameters['state']);
        $this->assertIsString($parameters['code']);
        $tokens = $this->exchange($connection, $parameters['code']);
        $this->assertSame(['wiki:read'], $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token'])->scopes);
    }

    #[Group('useDb')]
    public function testConsentPortConvertsInvalidConsentWithoutIssuingCode(): void
    {
        $connection = $this->fixture();
        $this->expectException(InvalidAuthorizationConsentException::class);
        $this->app()->make(AuthorizationConsentServiceInterface::class)->issueCode(
            $connection->identifier,
            new AuthorizationCodeParameters($connection->clientIdentifier, self::REDIRECT, ['wiki-images:write'], self::RESOURCE, str_repeat('a', 43), 'state')
        );
    }

    #[Group('useDb')]
    public function testConfidentialClientIsNotAcceptedByThePublicClientContract(): void
    {
        $first = $this->fixture();
        $client = $this->app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('Confidential', [self::REDIRECT], true);
        $connection = new AuthorizationConnection(
            (string) Str::uuid7(),
            $first->identityIdentifier,
            $client->id,
            $first->scopes,
            $first->targets,
            $first->originalAccountIdentifier,
            $first->originalPrincipalIdentifier,
            $first->initialAccountIdentifier,
            $first->resource,
            new DateTimeImmutable()
        );
        $this->expectException(InvalidAuthorizationConsentException::class);
        $this->app()->make(AuthorizationConsentServiceInterface::class)->createConnection($connection);
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
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        $encoded = explode('.', $tokens['access_token'])[1];
        $payload = json_decode((string) base64_decode(strtr($encoded, '-_', '+/'), true), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($payload);
        $payload[$claim] = $value;
        $key = config()->string('passport.private_key');
        $signed = JWT::encode($payload, $key, 'RS256');
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(McpAuthorizationService::class)->verify($signed);
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
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        Passport::client()->newQuery()->whereKey($connection->clientIdentifier)->update(['revoked' => true]);
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testPersistedAccessRevocationRejectsVerification(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        Passport::token()->newQuery()->update(['revoked' => true]);
        $this->expectException(InvalidAuthorizationTokenException::class);
        $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token']);
    }

    #[Group('useDb')]
    public function testIdentityIsMappedToPassportWithoutReplacingWebSessionGuard(): void
    {
        $connection = $this->fixture();
        $identity = Identity::query()->findOrFail((string) $connection->identityIdentifier);
        $this->assertSame('users', $identity->getProviderName());
        $this->assertSame('session', config('auth.guards.web.driver'));
        $tokens = $this->exchange($connection, $this->code($connection));
        $this->assertSame(1, $identity->tokens()->count());
        $this->assertFalse($identity->tokenCan('wiki:read'));
        $this->assertSame($connection->identifier, $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token'])->connection->identifier);
    }

    #[Group('useDb')]
    public function testLoopbackRedirectPortMustAlsoMatchExactly(): void
    {
        $first = $this->fixture();
        $client = $this->app()->make(ClientRepository::class)->createAuthorizationCodeGrantClient('Loopback', ['http://127.0.0.1:4000/callback'], false);
        $connection = new AuthorizationConnection(
            (string) Str::uuid7(),
            $first->identityIdentifier,
            $client->id,
            $first->scopes,
            $first->targets,
            $first->originalAccountIdentifier,
            $first->originalPrincipalIdentifier,
            $first->initialAccountIdentifier,
            $first->resource,
            new DateTimeImmutable()
        );
        $this->app()->make(AuthorizationConsentServiceInterface::class)->createConnection($connection);
        $this->expectException(OAuthServerException::class);
        $this->code($connection, ['redirect_uri' => 'http://127.0.0.1:5000/callback']);
    }

    #[Group('useDb')]
    public function testCallerTransactionRollsBackRefreshRotationAfterARealWriteFails(): void
    {
        $connection = $this->fixture();
        $tokens = $this->exchange($connection, $this->code($connection));
        $dispatcher = Mockery::mock(Dispatcher::class);
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $failure = new RuntimeException('Simulated post-insert failure');
        $dispatcher->shouldReceive('dispatch')->once()->andThrow($failure);
        $service = new McpAuthorizationService(
            $this->app()->make(AuthorizationConnectionRepositoryInterface::class),
            $this->app()->make(PassportClientRepository::class),
            $this->app()->make(ClientRepository::class),
            $dispatcher,
            $this->app()->make(Encrypter::class),
        );

        try {
            DB::transaction(fn () => $service->exchange((new ServerRequest('POST', '/oauth/token'))->withParsedBody([
                'grant_type' => 'refresh_token', 'client_id' => $connection->clientIdentifier, 'refresh_token' => $tokens['refresh_token'],
            ])));
            $this->fail('Expected the event boundary to fail after persistence.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertSame(1, Passport::token()->newQuery()->count());
        $this->assertSame(0, Passport::token()->newQuery()->where('revoked', true)->count());
        $this->assertSame(1, Passport::refreshToken()->newQuery()->count());
        $this->assertSame(0, Passport::refreshToken()->newQuery()->where('revoked', true)->count());
        $this->assertSame($connection->identifier, $this->app()->make(McpAuthorizationService::class)->verify($tokens['access_token'])->connection->identifier);
        $this->refresh($connection, $tokens['refresh_token']);
    }

    private function fixture(): AuthorizationConnection
    {
        return CreateAuthorizationConnection::create();
    }

    /** @param array<string, string> $overrides */
    private function code(AuthorizationConnection $connection, array $overrides = []): string
    {
        $query = array_replace([
            'client_id' => $connection->clientIdentifier, 'response_type' => 'code', 'redirect_uri' => self::REDIRECT,
            'resource' => self::RESOURCE, 'scope' => implode(' ', $connection->scopes),
            'code_challenge_method' => 'S256', 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
            'state' => 'test-state',
        ], $overrides);
        $response = $this->app()->make(McpAuthorizationService::class)->authorize(
            $connection->identifier,
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
    private function exchange(AuthorizationConnection $connection, string $code, array $overrides = []): array
    {
        return $this->tokenRequest(array_replace([
            'client_id' => $connection->clientIdentifier, 'grant_type' => 'authorization_code',
            'redirect_uri' => self::REDIRECT, 'code' => $code, 'code_verifier' => self::VERIFIER, 'resource' => self::RESOURCE,
        ], $overrides));
    }

    /** @param array<string, string> $overrides
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    private function refresh(AuthorizationConnection $connection, string $refresh, array $overrides = []): array
    {
        return $this->tokenRequest(array_replace([
            'client_id' => $connection->clientIdentifier, 'grant_type' => 'refresh_token', 'refresh_token' => $refresh,
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
