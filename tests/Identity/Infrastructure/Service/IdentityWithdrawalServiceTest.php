<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Http\Action\Identity\Command\WithdrawIdentity\WithdrawIdentityAction;
use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Models\Identity\Identity;
use DateTimeImmutable;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawIdentity\WithdrawIdentityInterface;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class IdentityWithdrawalServiceTest extends TestCase
{
    public function testAllSessionInvalidationAndLogoutRunOnlyAfterPhysicalDeletionCommits(): void
    {
        [$identityIdentifier, $accountId] = $this->createActor();
        $this->allowRecentAuthentication($identityIdentifier);
        /** @var AuthServiceInterface&MockInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        $invalidationObservation = null;
        $auth->shouldReceive('invalidateAllSessions')->once()->with($identityIdentifier)->andReturnUsing(function () use ($identityIdentifier, &$invalidationObservation): void {
            $invalidationObservation = [DB::transactionLevel(), DB::table('identities')->where('id', (string) $identityIdentifier)->exists()];
        })->globally()->ordered();
        $auth->shouldReceive('logout')->once()->globally()->ordered();
        $action = new WithdrawIdentityAction($this->app()->make(WithdrawIdentityInterface::class), new ActorContext($identityIdentifier, Language::JAPANESE), $auth, new NullLogger(), $this->app()->make(Request::class));

        try {
            $this->assertSame(204, $action()->getStatusCode());
            DB::commit();
            $this->assertSame([0, false], $invalidationObservation);
            $this->assertDatabaseMissing('accounts', ['id' => $accountId]);
        } finally {
            $this->removeCommittedFixtures($identityIdentifier, $accountId);
        }
    }

    public function testDeletedIdentityCannotLoadFromAnotherSessionEvenIfInvalidationFails(): void
    {
        [$identityIdentifier, $accountId] = $this->createActor();
        $this->allowRecentAuthentication($identityIdentifier);
        $identity = Identity::query()->findOrFail((string) $identityIdentifier);
        $session = new Store('withdrawal', new ArraySessionHandler(120), 'other-login-session');
        $this->app()->make('request')->setLaravelSession($session);
        $guard = new SessionGuard('web', new EloquentUserProvider($this->app()->make(Hasher::class), Identity::class), $session);
        $guard->login($identity);
        /** @var AuthServiceInterface&MockInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('invalidateAllSessions')->once()->andThrow(new RuntimeException('Redis unavailable'));
        $auth->shouldReceive('logout')->once()->andThrow(new RuntimeException('Session backend unavailable'));
        DB::table('identities')->where('id', (string) $identityIdentifier)->update(['profile_image' => 'images/withdrawal.webp']);
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('delete')->once()->andThrow(new RuntimeException('Image backend unavailable'));
        $this->app()->instance(ImageServiceInterface::class, $imageService);
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->with('Withdrawn identity session invalidation failed.', Mockery::type('array'));
        $logger->shouldReceive('error')->once()->with('Withdrawn identity logout failed.', Mockery::type('array'));
        $logger->shouldReceive('warning')->once()->with('Withdrawn identity profile image cleanup failed.', Mockery::type('array'));
        $logger->shouldReceive('warning')->once()->with('Failed to clear withdrawn identity account context.', Mockery::type('array'));
        $this->app()->instance(LoggerInterface::class, $logger);
        /** @var MockInterface $cacheLogger */
        $cacheLogger = Log::spy();
        Redis::shouldReceive('del', 'keys')->andThrow(new RuntimeException('Cache backend unavailable'));
        $action = new WithdrawIdentityAction($this->app()->make(WithdrawIdentityInterface::class), new ActorContext($identityIdentifier, Language::JAPANESE), $auth, $logger, $this->app()->make(Request::class));

        try {
            DB::commit();
            $this->assertSame(0, DB::transactionLevel());
            $this->assertSame(204, $action()->getStatusCode());
            $cacheLogger->shouldHaveReceived('warning')->with('Authentication context cache invalidation failed.', Mockery::type('array'));
            $cacheLogger->shouldHaveReceived('warning')->with('Authentication context cache enumeration failed.', Mockery::type('array'));
            $guard->forgetUser();
            $this->assertNull($guard->user());
            $this->assertDatabaseMissing('identities', ['id' => (string) $identityIdentifier]);
        } finally {
            $this->removeCommittedFixtures($identityIdentifier, $accountId);
        }
    }

    private function removeCommittedFixtures(IdentityIdentifier $identityIdentifier, string $accountId): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::table('identities')->where('id', (string) $identityIdentifier)->delete();
        DB::table('accounts')->where('id', $accountId)->delete();
        DB::table('archived_principals')->where('identity_id', (string) $identityIdentifier)->delete();
        DB::table('archived_identities')->where('identity_id', (string) $identityIdentifier)->delete();
        DB::table('archived_accounts')->where('account_id', $accountId)->delete();
        DB::beginTransaction();
    }

    public function testIndividualWithdrawalArchivesOnlyAllowlistedFieldsAndDeletesCredentials(): void
    {
        [$identityIdentifier, $accountId, $principalId] = $this->createActor();
        CreateIdentity::createSocialConnection($identityIdentifier, SocialProvider::GOOGLE, 'private-provider-id');
        $this->allowRecentAuthentication($identityIdentifier);

        $response = $this->action($identityIdentifier)();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing('identities', ['id' => (string) $identityIdentifier]);
        $this->assertDatabaseMissing('accounts', ['id' => $accountId]);
        $this->assertDatabaseMissing('identity_social_connections', ['identity_id' => (string) $identityIdentifier]);
        $this->assertDatabaseHas('archived_principals', ['identity_id' => (string) $identityIdentifier, 'principal_type' => 'account', 'principal_id' => $principalId, 'account_id' => $accountId]);
        $identity = DB::table('archived_identities')->where('identity_id', (string) $identityIdentifier)->first();
        $account = DB::table('archived_accounts')->where('account_id', $accountId)->first();
        $this->assertNotNull($identity);
        $this->assertNotNull($account);
        $this->assertSame($identity->archived_at, $account->archived_at);
        $this->assertSame(['identity_id', 'language', 'identity_created_at', 'archived_at'], Schema::getColumnListing('archived_identities'));
        $this->assertSame(['account_id', 'account_category', 'account_type', 'archived_at'], Schema::getColumnListing('archived_accounts'));
        $this->assertSame(['identity_id', 'principal_type', 'principal_id', 'account_id', 'archived_at'], Schema::getColumnListing('archived_principals'));
        $this->assertSame('ja', $identity->language);
        $this->assertNotNull($identity->identity_created_at);
        $this->assertSame('general', $account->account_category);
        $this->assertSame('individual', $account->account_type);
    }

    public function testMissingRecentAuthenticationDoesNotWriteAnything(): void
    {
        [$identityIdentifier, $accountId] = $this->createActor();
        /** @var StepUpAuthenticationStorageServiceInterface&MockInterface $authentication */
        $authentication = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $authentication->shouldReceive('requireValid')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $this->app()->instance(StepUpAuthenticationStorageServiceInterface::class, $authentication);

        $response = $this->action($identityIdentifier)();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('recent_authentication_required', (string) $response->getContent());
        $this->assertDatabaseHas('identities', ['id' => (string) $identityIdentifier]);
        $this->assertDatabaseHas('accounts', ['id' => $accountId]);
        $this->assertDatabaseCount('archived_identities', 0);
    }

    public function testDependentDeletionFailureRollsBackAllArchivesAndDeletes(): void
    {
        [$identityIdentifier, $accountId, $principalId] = $this->createActor();
        $this->allowRecentAuthentication($identityIdentifier);
        $failure = new RuntimeException('Injected dependent deletion failure');
        Event::listen(IdentityWithdrawing::class, static function () use ($failure): void {
            throw $failure;
        });

        try {
            $this->action($identityIdentifier)();
            $this->fail('Withdrawal must fail');
        } catch (InternalServerErrorHttpException $exception) {
            $this->assertSame($failure, $exception->getPrevious());
            $this->assertDatabaseHas('identities', ['id' => (string) $identityIdentifier]);
            $this->assertDatabaseHas('accounts', ['id' => $accountId]);
            $this->assertDatabaseHas('account_principals', ['id' => $principalId]);
            $this->assertDatabaseCount('archived_identities', 0);
            $this->assertDatabaseCount('archived_accounts', 0);
            $this->assertDatabaseCount('archived_principals', 0);
        }
    }

    /** @return array{IdentityIdentifier, string, string} */
    private function createActor(): array
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = StrTestHelper::generateUuid();
        $principalId = StrTestHelper::generateUuid();
        CreateIdentity::create($identityIdentifier, ['identity_name' => 'private name', 'email' => 'private@example.com']);
        CreateAccount::create($accountId);
        DB::table('account_principals')->insert(['id' => $principalId, 'identity_id' => (string) $identityIdentifier, 'account_id' => $accountId, 'created_at' => now(), 'updated_at' => now()]);

        return [$identityIdentifier, $accountId, $principalId];
    }

    private function allowRecentAuthentication(IdentityIdentifier $identityIdentifier): void
    {
        /** @var StepUpAuthenticationStorageServiceInterface&MockInterface $authentication */
        $authentication = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $now = new DateTimeImmutable();
        $authentication->shouldReceive('requireValid')->once()->withArgs(static fn (IdentityIdentifier $actual, StepUpAuthenticationScope $scope): bool => (string) $actual === (string) $identityIdentifier)
            ->andReturn(new StepUpAuthentication($identityIdentifier, StepUpAuthenticationMethod::PASSKEY, $now, StepUpAuthenticationScope::RECENT_AUTHENTICATION, $now->modify('+10 minutes')));
        $this->app()->instance(StepUpAuthenticationStorageServiceInterface::class, $authentication);
    }

    private function action(IdentityIdentifier $identityIdentifier): WithdrawIdentityAction
    {
        /** @var AuthServiceInterface&MockInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        // Tests hold an outer transaction: invalidation must never run before its commit.
        $auth->shouldNotReceive('invalidateAllSessions');
        $auth->shouldNotReceive('logout');

        return new WithdrawIdentityAction($this->app()->make(WithdrawIdentityInterface::class), new ActorContext($identityIdentifier, Language::JAPANESE), $auth, new NullLogger(), $this->app()->make(Request::class));
    }
}
