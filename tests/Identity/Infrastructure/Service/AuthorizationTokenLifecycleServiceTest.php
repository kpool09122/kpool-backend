<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Models\Identity\AuthorizationConsent as ConsentModel;
use Application\Models\Identity\McpAccessToken;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use League\OAuth2\Server\Exception\OAuthServerException;
use Mockery;
use Mockery\MockInterface;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInput;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInterface;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationOutput;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Infrastructure\Service\McpAuthorizationService;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAuthorizationConsent;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationTokenLifecycleServiceTest extends TestCase
{
    private const string VERIFIER = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';

    public function testRevocationClearsAllSourceTokenCachesOnlyAfterCommitAndPreservesOtherConnections(): void
    {
        $consent = CreateAuthorizationConsent::create();
        $second = ConsentModel::query()->findOrFail((string) $consent->identifier())->replicate();
        $second->forceFill(['id' => (string) Str::uuid7()])->save();
        $repository = $this->app()->make(AuthorizationConsentRepositoryInterface::class);
        $other = $repository->find(new AuthorizationConsentIdentifier($second->id));
        self::assertNotNull($other);
        $tokens = $this->sourceTokens($consent);
        $moreTokens = $this->sourceTokens($consent);
        $otherTokens = $this->sourceTokens($other);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        $moreApi = $service->issue($moreTokens['access_token']);
        $otherApi = $service->issue($otherTokens['access_token']);
        $service->verify($api->reveal());
        $service->verify($moreApi->reveal());
        $service->verify($otherApi->reveal());
        $prefixes = ['current-account:', 'auth-context:account:'];
        $keys = [];
        foreach ($prefixes as $prefix) {
            foreach (['consent:' . $consent->identifier(), 'consent:' . $other->identifier(), 'browser-session'] as $suffix) {
                $key = $prefix . $consent->identityIdentifier() . ':' . $suffix;
                Redis::set($key, 'selection');
                $keys[] = $key;
            }
        }

        try {
            $this->configureCommitCallbacks();
            DB::beginTransaction();
            $this->revoke($consent);
            self::assertSame('selection', Redis::get($keys[0]));
            $service->verify($api->reveal());
            DB::commit();
            foreach ([0, 3] as $index) {
                self::assertNull(Redis::get($keys[$index]));
            }
            foreach ([1, 2, 4, 5] as $index) {
                self::assertSame('selection', Redis::get($keys[$index]));
            }
            foreach ([$api, $moreApi] as $revoked) {
                try {
                    $service->verify($revoked->reveal());
                    self::fail('Revoked cached API token accepted.');
                } catch (InvalidAuthorizationTokenException $exception) {
                    self::assertSame('Invalid authorization token.', $exception->getMessage());
                }
            }
            $service->verify($otherApi->reveal());
            self::assertSame(0, McpAccessToken::query()->where('authorization_consent_id', (string) $consent->identifier())->where('revoked', false)->count());
            $revokedAt = ConsentModel::query()->findOrFail((string) $consent->identifier())->revoked_at;
            $this->revoke($consent);
            self::assertEquals($revokedAt, ConsentModel::query()->findOrFail((string) $consent->identifier())->revoked_at);
            $this->expectException(InvalidAuthorizationTokenException::class);
            $service->issue($tokens['access_token']);
        } finally {
            foreach ($keys as $key) {
                Redis::del($key);
            }
        }
    }

    public function testWithdrawalDeletesAllAuthorizationRowsBeforeIdentityAndInvalidatesWarmedTokensAfterCommit(): void
    {
        $consent = CreateAuthorizationConsent::create();
        $second = ConsentModel::query()->findOrFail((string) $consent->identifier())->replicate();
        $second->forceFill(['id' => (string) Str::uuid7()])->save();
        $repository = $this->app()->make(AuthorizationConsentRepositoryInterface::class);
        $otherConsent = $repository->find(new AuthorizationConsentIdentifier($second->id));
        self::assertNotNull($otherConsent);
        $tokens = $this->sourceTokens($consent);
        $otherTokens = $this->sourceTokens($otherConsent);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $apis = [$service->issue($tokens['access_token']), $service->issue($otherTokens['access_token'])];
        foreach ($apis as $api) {
            $service->verify($api->reveal());
        }
        $this->authorizeWithdrawal($consent);
        $selectionKey = 'current-account:' . $consent->identityIdentifier() . ':consent:' . $consent->identifier();
        $contextKey = 'auth-context:account:' . $consent->identityIdentifier() . ':consent:' . $consent->identifier();
        Redis::set($selectionKey, 'selection');
        Redis::set($contextKey, 'context');
        $this->configureCommitCallbacks();
        DB::beginTransaction();
        $this->withdraw($consent);
        self::assertSame(0, DB::table('identities')->where('id', (string) $consent->identityIdentifier())->count());
        self::assertSame(0, DB::table('oauth_access_tokens')->count());
        self::assertSame(0, DB::table('oauth_auth_codes')->count());
        self::assertSame(0, DB::table('oauth_refresh_tokens')->count());
        self::assertSame(0, ConsentModel::query()->count());
        foreach ($apis as $api) {
            $service->verify($api->reveal());
        }
        self::assertSame('selection', Redis::get($selectionKey));
        self::assertSame('context', Redis::get($contextKey));
        DB::commit();
        self::assertNull(Redis::get($selectionKey));
        self::assertNull(Redis::get($contextKey));
        foreach ($apis as $api) {
            try {
                $service->verify($api->reveal());
                self::fail('Withdrawn token accepted.');
            } catch (InvalidAuthorizationTokenException $exception) {
                self::assertSame('Invalid authorization token.', $exception->getMessage());
            }
        }
    }

    public function testWithdrawalRollbackRestoresRealDeletedRowsAndDoesNotInvalidateCaches(): void
    {
        $consent = CreateAuthorizationConsent::create();
        $tokens = $this->sourceTokens($consent);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        $service->verify($api->reveal());
        $this->authorizeWithdrawal($consent);
        $selectionKey = 'current-account:' . $consent->identityIdentifier() . ':consent:' . $consent->identifier();
        $contextKey = 'auth-context:account:' . $consent->identityIdentifier() . ':consent:' . $consent->identifier();
        Redis::set($selectionKey, 'selection');
        Redis::set($contextKey, 'context');
        $this->configureCommitCallbacks();

        try {
            DB::transaction(function () use ($consent): void {
                $this->withdraw($consent);
                self::assertSame(0, ConsentModel::query()->count());
                self::assertSame(0, DB::table('identities')->where('id', (string) $consent->identityIdentifier())->count());

                throw new RuntimeException('Late failure after real deletion.');
            });
        } catch (RuntimeException $exception) {
            self::assertSame('Late failure after real deletion.', $exception->getMessage());
        }
        self::assertNull(ConsentModel::query()->findOrFail((string) $consent->identifier())->revoked_at);
        self::assertSame(1, DB::table('identities')->where('id', (string) $consent->identityIdentifier())->count());
        DB::enableQueryLog();
        $service->verify($api->reveal());
        self::assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        self::assertSame('selection', Redis::get($selectionKey));
        self::assertSame('context', Redis::get($contextKey));
        Redis::del($selectionKey, $contextKey);
        $service->issue($tokens['access_token']);
        $this->exchange(['grant_type' => 'refresh_token', 'client_id' => (string) $consent->clientIdentifier(), 'refresh_token' => $tokens['refresh_token']]);
    }

    private function authorizeWithdrawal(AuthorizationConsent $consent): void
    {
        CreateAccount::create((string) $consent->originalAccountIdentifier());
        DB::table('account_principals')->insert(['id' => (string) $consent->originalPrincipalIdentifier(), 'identity_id' => (string) $consent->identityIdentifier(), 'account_id' => (string) $consent->originalAccountIdentifier()]);
        /** @var StepUpAuthenticationStorageServiceInterface&MockInterface $stepUpAuthenticationStorageService */
        $stepUpAuthenticationStorageService = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUpAuthenticationStorageService->shouldReceive('requireValid')->once()->andReturn(new StepUpAuthentication(
            $consent->identityIdentifier(),
            StepUpAuthenticationMethod::PASSKEY,
            new DateTimeImmutable(),
            StepUpAuthenticationScope::RECENT_AUTHENTICATION,
            new DateTimeImmutable('+5 minutes'),
        ));
        $this->app()->instance(StepUpAuthenticationStorageServiceInterface::class, $stepUpAuthenticationStorageService);
    }

    private function withdraw(AuthorizationConsent $consent): void
    {
        $this->app()->make(WithdrawFromServiceInterface::class)->process(
            new WithdrawFromServiceInput($consent->identityIdentifier(), 'test-identity'),
            new WithdrawFromServiceOutput(),
        );
    }

    public function testRevocationRollbackPreservesWarmedAuthorizationAndSelectionAndAllowsRefresh(): void
    {
        $consent = CreateAuthorizationConsent::create();
        $tokens = $this->sourceTokens($consent);
        $service = $this->app()->make(InternalApiTokenServiceInterface::class);
        $api = $service->issue($tokens['access_token']);
        $service->verify($api->reveal());
        $key = 'current-account:' . $consent->identityIdentifier() . ':consent:' . $consent->identifier();
        $contextKey = 'auth-context:account:' . $consent->identityIdentifier() . ':consent:' . $consent->identifier();
        Redis::set($key, 'selection');
        Redis::set($contextKey, 'context');
        $this->configureCommitCallbacks();

        try {
            DB::beginTransaction();
            $this->revoke($consent);
            self::assertNotNull(ConsentModel::query()->findOrFail((string) $consent->identifier())->revoked_at);
            self::assertSame(1, McpAccessToken::query()->where('revoked', true)->count());
            DB::rollBack();
            self::assertNull(ConsentModel::query()->findOrFail((string) $consent->identifier())->revoked_at);
            self::assertSame('selection', Redis::get($key));
            self::assertSame('context', Redis::get($contextKey));
            DB::enableQueryLog();
            DB::flushQueryLog();
            $service->verify($api->reveal());
            self::assertSame([], DB::getQueryLog());
            DB::disableQueryLog();
            $this->exchange(['grant_type' => 'refresh_token', 'client_id' => (string) $consent->clientIdentifier(), 'refresh_token' => $tokens['refresh_token']]);
        } finally {
            Redis::del($key, $contextKey);
        }
    }

    public function testRevocationRejectsRefreshExchange(): void
    {
        $consent = CreateAuthorizationConsent::create();
        $tokens = $this->sourceTokens($consent);
        $this->revoke($consent);
        $this->expectException(OAuthServerException::class);
        $this->exchange(['grant_type' => 'refresh_token', 'client_id' => (string) $consent->clientIdentifier(), 'refresh_token' => $tokens['refresh_token']]);
    }

    private function configureCommitCallbacks(): void
    {
        $connection = DB::getDefaultConnection();
        $manager = new DatabaseTransactionsManager([$connection]);
        $manager->begin($connection, DB::transactionLevel());
        DB::connection()->setTransactionManager($manager);
    }

    private function revoke(AuthorizationConsent $consent): void
    {
        $this->app()->make(RevokeAuthorizationInterface::class)->process(
            new RevokeAuthorizationInput($consent->identityIdentifier(), $consent->identifier()),
            new RevokeAuthorizationOutput(),
        );
    }

    /** @return array{access_token: string, refresh_token: string} */
    private function sourceTokens(AuthorizationConsent $consent): array
    {
        config()->set('internal_api_token', ['resource' => 'https://api.example.test', 'lifetime_seconds' => 60]);
        $redirect = $this->app()->make(AuthorizationConsentServiceInterface::class)->issueCode(
            $consent->identifier(),
            new AuthorizationCodeParameters(
                $consent->clientIdentifier(),
                CreateAuthorizationConsent::REDIRECT,
                $consent->scopes(),
                CreateAuthorizationConsent::RESOURCE,
                rtrim(strtr(base64_encode(hash('sha256', self::VERIFIER, true)), '+/', '-_'), '='),
                'state',
            ),
        );
        parse_str((string) parse_url($redirect, PHP_URL_QUERY), $parameters);
        self::assertIsString($parameters['code']);

        return $this->exchange([
            'grant_type' => 'authorization_code', 'client_id' => (string) $consent->clientIdentifier(),
            'code' => $parameters['code'], 'code_verifier' => self::VERIFIER,
            'redirect_uri' => CreateAuthorizationConsent::REDIRECT, 'resource' => CreateAuthorizationConsent::RESOURCE,
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
