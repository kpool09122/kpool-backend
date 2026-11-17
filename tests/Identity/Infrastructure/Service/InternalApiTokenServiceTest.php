<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Identity\McpAccessToken;
use Application\Models\Identity\McpClient;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RedisException;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConnectionServiceInterface;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Infrastructure\Service\McpAuthorizationService;
use Tests\Helper\CreateAuthorizationConnection;
use Tests\TestCase;

#[Group('useDb')]
class InternalApiTokenServiceTest extends TestCase
{
    private const string VERIFIER = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';

    public function testIssuesAnApiTokenAndPreservesTheValidatedAuthorizationContext(): void
    {
        $connection = CreateAuthorizationConnection::create();
        config()->set('internal_api_token', ['resource' => 'https://api.example.test', 'lifetime_seconds' => 60]);
        $tokens = $this->sourceTokens($connection);
        $source = $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($tokens['access_token']);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $issued = $service->issue($tokens['access_token']);
        $context = $service->verify($issued->reveal());
        self::assertEquals($connection->identifier(), $context->connectionIdentifier());
        self::assertEquals($connection->identityIdentifier(), $context->identityIdentifier());
        self::assertEquals($connection->clientIdentifier(), $context->clientIdentifier());
        self::assertSame($source->tokenIdentifier, $context->sourceTokenIdentifier());
        self::assertSame($source->scopes, $context->scopes());
        self::assertLessThanOrEqual($source->expiresAt, $issued->expiresAt);
        self::assertEquals($issued->expiresAt, $context->expiresAt());
        self::assertLessThanOrEqual(60, $issued->expiresAt->getTimestamp() - now()->getTimestamp());
    }

    public function testMcpAndInternalApiTokensCannotBeUsedAtTheOtherAudience(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        foreach (['api verifier' => fn () => $service->verify($tokens['access_token']),
            'mcp verifier' => fn () => $this->app()->make(AuthorizationTokenVerifierInterface::class)->verify($api->reveal()),
            're-exchange api' => fn () => $service->issue($api->reveal())] as $label => $operation) {
            try {
                $operation();
                self::fail('Accepted wrong audience: ' . $label);
            } catch (InvalidAuthorizationTokenException $exception) {
                self::assertSame('Invalid authorization token.', $exception->getMessage());
            }
        }
    }

    public function testTokenIssuedBeforeConnectionClaimRenameRemainsValid(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $payload = $this->payload($service->issue($tokens['access_token'])->reveal());
        $payload['authorization_consent_id'] = $payload['authorization_connection_id'];
        unset($payload['authorization_connection_id']);
        $signed = JWT::encode($payload, config()->string('passport.private_key'), 'RS256', null, ['typ' => 'internal-api+jwt']);

        self::assertEquals($connection->identifier(), $service->verify($signed)->connectionIdentifier());
    }

    public function testVerificationReusesRedisAuthorizationAcrossApiTokensFromTheSameSource(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $first = $service->issue($tokens['access_token']);
        $second = $service->issue($tokens['access_token']);

        DB::enableQueryLog();

        try {
            $firstContext = $service->verify($first->reveal());
            self::assertNotEmpty(DB::getQueryLog());
            DB::flushQueryLog();

            $secondContext = $service->verify($second->reveal());
            self::assertSame([], DB::getQueryLog());
            self::assertSame($firstContext->sourceTokenIdentifier(), $secondContext->sourceTokenIdentifier());
            self::assertEquals($second->expiresAt, $secondContext->expiresAt());
        } finally {
            DB::disableQueryLog();
        }
    }

    public function testVerificationFallsBackToDatabaseWhenRedisIsUnavailable(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        Cache::shouldReceive('store')->once()->with('redis')->andThrow(new RedisException('Connection lost.'));

        self::assertEquals($connection->identifier(), $service->verify($api->reveal())->connectionIdentifier());
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidClaims(): array
    {
        return [
            'issuer' => ['iss', 'https://other.test'],
            'MCP audience' => ['aud', [CreateAuthorizationConnection::RESOURCE]],
            'multiple audience' => ['aud', ['https://api.example.test', CreateAuthorizationConnection::RESOURCE]],
            'identity' => ['sub', 'other'],
            'client' => ['client_id', 'other'],
            'connection' => ['authorization_connection_id', '019ab000-0000-7000-8000-000000000001'],
            'source missing' => ['source_token_id', 'missing'],
            'source not scalar' => ['source_token_id', ['other']],
            'scope escalation' => ['scopes', ['wiki:read', 'wiki-images:write']],
            'scope narrowing not issued' => ['scopes', ['wiki:read']],
            'expired' => ['exp', 1],
            'future issued' => ['iat', 9999999999],
            'future activation' => ['nbf', 9999999999],
            'long lifetime' => ['iat', 1],
            'empty jti' => ['jti', ''],
        ];
    }

    #[DataProvider('invalidClaims')]
    public function testValidSignaturesCannotExpandOrChangeTheSourceBinding(string $claim, mixed $value): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        $payload = $this->payload($api->reveal());
        $payload[$claim] = $value;
        $signed = JWT::encode($payload, config()->string('passport.private_key'), 'RS256', null, ['typ' => 'internal-api+jwt']);
        $this->expectException(InvalidAuthorizationTokenException::class);
        $service->verify($signed);
    }

    /** @return array<string, array{string}> */
    public static function malformedTokens(): array
    {
        return ['empty' => [''], 'malformed' => ['not-a-jwt'], 'bad base64' => ['%.%.%'], 'empty object' => ['e30.e30.e30']];
    }

    #[DataProvider('malformedTokens')]
    public function testMalformedCredentialsAreRejectedWithoutRetainingThem(string $token): void
    {
        CreateAuthorizationConnection::create();
        config()->set('internal_api_token.resource', 'https://api.example.test');
        config()->set('mcp_oauth', ['issuer' => 'https://auth.example.test', 'resource' => CreateAuthorizationConnection::RESOURCE]);

        try {
            $this->app()->make(InternalApiTokenServiceInterface::class)->verify($token);
            self::fail('Accepted malformed credential.');
        } catch (InvalidAuthorizationTokenException $exception) {
            self::assertSame('Invalid authorization token.', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }

    public function testInvalidSourceCredentialsDoNotEscapeInExceptionChains(): void
    {
        CreateAuthorizationConnection::create();
        config()->set('internal_api_token.resource', 'https://api.example.test');

        try {
            $this->app()->make(InternalApiTokenServiceInterface::class)->issue('secret-invalid-source');
            self::fail('Accepted invalid source.');
        } catch (InvalidAuthorizationTokenException $exception) {
            self::assertNull($exception->getPrevious());
            self::assertStringNotContainsString('secret-invalid-source', (string) $exception);
        }
    }

    /** @return array<string, array{string}> */
    public static function invalidResources(): array
    {
        return ['empty' => [''], 'same as MCP' => [CreateAuthorizationConnection::RESOURCE]];
    }

    #[DataProvider('invalidResources')]
    public function testApiAndMcpResourcesMustBeDistinctAndNonEmpty(string $resource): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        config()->set('internal_api_token.resource', $resource);
        $this->expectException(InvalidInternalApiTokenConfigurationException::class);
        $this->expectExceptionMessage('Configure distinct MCP and internal API resources and a non-empty issuer.');
        $this->app()->make(InternalApiTokenServiceInterface::class)->issue($tokens['access_token']);
    }

    public function testSignatureTamperingIsRejected(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        $parts = explode('.', $api->reveal());
        $parts[1] = rtrim(strtr(base64_encode('{"sub":"other"}'), '+/', '-_'), '=');
        $this->expectException(InvalidAuthorizationTokenException::class);
        $service->verify(implode('.', $parts));
    }

    /** @return array<string, array{string}> */
    public static function invalidSourceStates(): array
    {
        return ['source revoked' => ['token'], 'connection revoked' => ['connection'], 'client revoked' => ['client'],
            'source deleted' => ['deleted'], 'expired source' => ['expired'], 'source identity changed' => ['identity'],
            'source scope changed' => ['scope']];
    }

    #[DataProvider('invalidSourceStates')]
    public function testPreviouslyVerifiedApiTokenIsRejectedAfterSourceStateChangesAndCacheIsForgotten(string $state): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        $context = $service->verify($api->reveal());
        $source = McpAccessToken::query()->findOrFail($context->sourceTokenIdentifier());
        switch ($state) {
            case 'token': $source->revoke();

                break;
            case 'connection':
                $connection->revoke(new DateTimeImmutable());
                $this->app()->make(AuthorizationConnectionRepositoryInterface::class)->save($connection);

                break;
            case 'client':
                McpClient::query()->findOrFail((string) $connection->clientIdentifier())->forceFill(['revoked' => true])->save();

                break;
            case 'deleted': $source->delete();

                break;
            case 'expired': $source->forceFill(['expires_at' => now()->subSecond()])->save();

                break;
            case 'identity': $source->forceFill(['user_id' => null])->save();

                break;
            case 'scope': $source->forceFill(['scopes' => ['wiki:read']])->save();

                break;
        }
        $service->forgetCachedAuthorization($context->sourceTokenIdentifier());
        $this->expectException(InvalidAuthorizationTokenException::class);
        $service->verify($api->reveal());
    }

    public function testRefreshNarrowingAndInternalReissuancePreserveTheConnectionWithoutRegainingScopes(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $oldApi = $service->issue($tokens['access_token']);
        $oldContext = $service->verify($oldApi->reveal());
        request()->attributes->set(ApiAuthenticationContext::class, $oldContext);
        $principal = new PrincipalIdentifier((string) $connection->originalPrincipalIdentifier());
        $selected = new CurrentAccount($connection->identityIdentifier(), $connection->originalAccountIdentifier(), $principal, $connection->originalAccountIdentifier(), $principal, null);
        $currentAccountService = $this->app()->make(CurrentAccountServiceInterface::class);
        $currentAccountService->save($selected);

        // Run commit callbacks for the refresh transaction inside the test's outer rollback transaction.
        $connectionName = DB::getDefaultConnection();
        $transactions = new DatabaseTransactionsManager([$connectionName]);
        $transactions->begin($connectionName, DB::transactionLevel());
        DB::connection()->setTransactionManager($transactions);
        $refreshed = DB::transaction(function () use ($connection, $tokens, $service, $oldApi): array {
            $refreshed = $this->exchange(['grant_type' => 'refresh_token', 'client_id' => (string) $connection->clientIdentifier(),
                'refresh_token' => $tokens['refresh_token'], 'scope' => 'wiki:read']);
            $service->verify($oldApi->reveal());

            return $refreshed;
        });
        $first = $service->issue($refreshed['access_token']);
        $second = $service->issue($refreshed['access_token']);
        self::assertNotSame($first->reveal(), $second->reveal());
        foreach ([$first, $second] as $api) {
            $context = $service->verify($api->reveal());
            self::assertEquals($connection->identifier(), $context->connectionIdentifier());
            self::assertSame([AuthorizationScope::WikiRead], $context->scopes());
            self::assertNotSame($oldContext->sourceTokenIdentifier(), $context->sourceTokenIdentifier());
            request()->attributes->set(ApiAuthenticationContext::class, $context);
            self::assertEquals($selected, $currentAccountService->find($connection->identityIdentifier()));
        }
        $this->expectException(InvalidAuthorizationTokenException::class);
        $service->verify($oldApi->reveal());
    }

    public function testConfiguredLifetimeAndExactExpirationBoundary(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $this->travelTo(now()->startOfSecond());
        config()->set('internal_api_token.lifetime_seconds', 15);
        $api = $service->issue($tokens['access_token']);
        self::assertSame(15, $api->expiresAt->getTimestamp() - now()->getTimestamp());
        $this->travel(14)->seconds();
        $service->verify($api->reveal());
        $this->travel(1)->seconds();
        $this->expectException(InvalidAuthorizationTokenException::class);
        $service->verify($api->reveal());
    }

    public function testLifetimeCannotExceedTheRemainingSourceLifetime(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $this->travelTo(now()->startOfSecond());
        McpAccessToken::query()->update(['expires_at' => now()->addSeconds(10)]);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        self::assertSame(10, $api->expiresAt->getTimestamp() - now()->getTimestamp());
        $service->verify($api->reveal());
        $this->travel(10)->seconds();
        $this->expectException(InvalidAuthorizationTokenException::class);
        $service->verify($api->reveal());
    }

    /** @return array<string, array{int}> */
    public static function invalidLifetimes(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'over maximum' => [61]];
    }

    #[DataProvider('invalidLifetimes')]
    public function testInvalidLifetimeConfigurationDoesNotIssueTokens(int $seconds): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        config()->set('internal_api_token.lifetime_seconds', $seconds);
        $this->expectException(InvalidInternalApiTokenConfigurationException::class);
        $this->expectExceptionMessage('Internal API token lifetime must be between 1 and 60 seconds.');
        $this->app()->make(InternalApiTokenServiceInterface::class)->issue($tokens['access_token']);
    }

    public function testOriginalAccountAndPrincipalAreResolvedFromThePersistedConnection(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        $payload = $this->payload($api->reveal());
        $payload['original_account_id'] = 'other-account';
        $payload['original_principal_id'] = 'other-account';
        $signed = JWT::encode($payload, config()->string('passport.private_key'), 'RS256', null, ['typ' => 'internal-api+jwt']);
        $context = $service->verify($signed);
        self::assertEquals($connection->originalAccountIdentifier(), $context->originalAccountIdentifier());
        self::assertEquals($connection->originalPrincipalIdentifier(), $context->originalPrincipalIdentifier());
    }

    public function testNoApiCredentialsArePersistedAsPassportOrRefreshTokens(): void
    {
        $connection = CreateAuthorizationConnection::create();
        $tokens = $this->sourceTokens($connection);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $service->issue($tokens['access_token']);
        $service->issue($tokens['access_token']);
        self::assertSame(1, Passport::token()->newQuery()->count());
        self::assertSame(1, Passport::refreshToken()->newQuery()->count());
        self::assertSame('session', config('auth.guards.web.driver'));
    }

    /** @return array<array-key, mixed> */
    private function payload(string $token): array
    {
        $payload = json_decode((string) base64_decode(strtr(explode('.', $token)[1], '-_', '+/'), true), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    /** @return array{access_token: string, refresh_token: string} */
    private function sourceTokens(AuthorizationConnection $connection): array
    {
        config()->set('internal_api_token', ['resource' => 'https://api.example.test', 'lifetime_seconds' => 60]);
        $redirect = $this->app()->make(AuthorizationConnectionServiceInterface::class)->issueCode(
            $connection->identifier(),
            new AuthorizationCodeParameters(
                $connection->clientIdentifier(),
                CreateAuthorizationConnection::REDIRECT,
                $connection->scopes(),
                CreateAuthorizationConnection::RESOURCE,
                rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
                'state',
            ),
        );
        parse_str((string) parse_url($redirect, PHP_URL_QUERY), $parameters);
        self::assertIsString($parameters['code']);

        return $this->exchange([
            'grant_type' => 'authorization_code', 'client_id' => (string) $connection->clientIdentifier(),
            'code' => $parameters['code'], 'code_verifier' => self::VERIFIER,
            'redirect_uri' => CreateAuthorizationConnection::REDIRECT, 'resource' => CreateAuthorizationConnection::RESOURCE,
        ]);
    }

    /**
     * @param array<string, string> $parameters
     * @return array{access_token: string, refresh_token: string}
     */
    private function exchange(array $parameters): array
    {
        $response = $this->app()->make(McpAuthorizationService::class)->exchange((new ServerRequest('POST', '/oauth/token'))->withParsedBody($parameters));
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertIsString($data['access_token']);
        self::assertIsString($data['refresh_token']);

        return ['access_token' => $data['access_token'], 'refresh_token' => $data['refresh_token']];
    }
}
