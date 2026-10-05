<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use Application\Http\Action\Identity\Command\WithdrawFromService\WithdrawFromServiceAction;
use Application\Http\Action\Identity\Command\WithdrawFromService\WithdrawFromServiceRequest;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Database\Seeders\SiteManagementAuthorizationSeeder;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ComposedIdentityWithdrawalTest extends TestCase
{
    public function testRealListenersArchiveAndDeleteTheEntireSubjectAndPreserveAnotherSubject(): void
    {
        $subject = $this->createSubject();
        $other = $this->createSubject();
        $history = $this->createSiteHistory($subject);
        $before = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $response = $this->action($subject['identities'])(WithdrawFromServiceRequest::create('/', 'DELETE', ['confirmationIdentityName' => 'Private person']));
        $after = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->assertSame(204, $response->getStatusCode());
        foreach ($subject as $table => $id) {
            $this->assertDatabaseMissing($table, ['id' => $id]);
            $this->assertDatabaseHas($table, ['id' => $other[$table]]);
        }
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_id' => $subject['site_management_principals']]);
        $this->assertSiteHistory($subject, $history);
        $archive = DB::table('archived_identities')->where('identity_id', $subject['identities'])->first();
        $this->assertNotNull($archive);
        $this->assertSame(['id', 'identity_id', 'language', 'identity_created_at', 'archived_at'], array_keys((array) $archive));
        $this->assertSame('ja', $archive->language);
        $this->assertNotNull($archive->identity_created_at);
        $accountArchive = DB::table('archived_accounts')->where('account_id', $subject['accounts'])->first();
        $this->assertNotNull($accountArchive);
        $this->assertSame([
            'id' => $accountArchive->id, 'account_id' => $subject['accounts'], 'account_category' => 'general', 'account_type' => 'individual', 'archived_at' => $accountArchive->archived_at,
        ], (array) $accountArchive);
        foreach ([$archive->archived_at, $accountArchive->archived_at] as $archivedAt) {
            $this->assertGreaterThanOrEqual($before, $archivedAt);
            $this->assertLessThanOrEqual($after, $archivedAt);
        }
        foreach (['account' => 'account_principals', 'wiki' => 'wiki_principals'] as $type => $table) {
            $principalArchive = DB::table('archived_principals')->where('principal_type', $type)->where('principal_id', $subject[$table])->first();
            $this->assertNotNull($principalArchive);
            $this->assertSame([
                'id' => $principalArchive->id, 'identity_id' => $subject['identities'], 'principal_type' => $type, 'principal_id' => $subject[$table],
                'account_id' => $subject['accounts'], 'archived_at' => $principalArchive->archived_at,
            ], (array) $principalArchive);
            $this->assertGreaterThanOrEqual($before, $principalArchive->archived_at);
            $this->assertLessThanOrEqual($after, $principalArchive->archived_at);
        }
        $this->assertDatabaseMissing('archived_identities', ['identity_id' => $other['identities']]);
        $this->assertDatabaseMissing('archived_accounts', ['account_id' => $other['accounts']]);
    }

    public function testDownstreamFailureRollsBackEveryContextAndArchive(): void
    {
        $subject = $this->createSubject();
        $history = $this->createSiteHistory($subject);
        /** @var AuthContextCache&MockInterface $authContextCache */
        $authContextCache = Mockery::mock(AuthContextCache::class);
        $authContextCache->shouldNotReceive('forgetActor', 'forgetAccount', 'forgetWiki');
        $this->app()->instance(AuthContextCache::class, $authContextCache);
        $observedDeletions = [];
        $failure = new RuntimeException('Downstream withdrawal failure');
        Event::listen(IdentityWithdrawing::class, function () use ($subject, &$observedDeletions, $failure): void {
            foreach (['accounts', 'wiki_principals', 'site_management_principals'] as $table) {
                $observedDeletions[$table] = ! DB::table($table)->where('id', $subject[$table])->exists();
            }

            throw $failure;
        });

        try {
            $this->action($subject['identities'])(WithdrawFromServiceRequest::create('/', 'DELETE', ['confirmationIdentityName' => 'Private person']));
            $this->fail('Expected transaction rollback');
        } catch (InternalServerErrorHttpException $exception) {
            $this->assertSame($failure, $exception->getPrevious());
            $this->assertSame(['accounts' => true, 'wiki_principals' => true, 'site_management_principals' => true], $observedDeletions);
            foreach ($subject as $table => $id) {
                $this->assertDatabaseHas($table, ['id' => $id]);
            }
            $this->assertDatabaseHas('site_management_principal_group_memberships', ['principal_id' => $subject['site_management_principals'], 'principal_group_id' => SiteManagementAuthorizationSeeder::GENERAL_GROUP]);
            $this->assertSiteHistory($subject, $history);
            foreach (['archived_identities', 'archived_accounts', 'archived_principals'] as $table) {
                $this->assertDatabaseCount($table, 0);
            }
        }
    }

    /** @return array<string, string> */
    private function createSubject(): array
    {
        $ids = [];
        foreach (['identities', 'accounts', 'account_principals', 'wiki_principals', 'passkey_users', 'passkey_credentials', 'site_management_principals'] as $table) {
            $ids[$table] = StrTestHelper::generateUuid();
        }
        $identityIdentifier = new IdentityIdentifier($ids['identities']);
        CreateIdentity::create($identityIdentifier, ['email' => $ids['identities'] . '@example.com', 'identity_name' => 'Private person']);
        CreateAccount::create($ids['accounts']);
        DB::table('account_principals')->insert(['id' => $ids['account_principals'], 'identity_id' => $ids['identities'], 'account_id' => $ids['accounts']]);
        CreatePrincipal::create(new PrincipalIdentifier($ids['wiki_principals']), $identityIdentifier, new AccountIdentifier($ids['accounts']));
        DB::table('passkey_users')->insert(['id' => $ids['passkey_users'], 'identity_id' => $ids['identities']]);
        DB::table('passkey_credentials')->insert(['id' => $ids['passkey_credentials'], 'passkey_user_id' => $ids['passkey_users'], 'credential_id' => $ids['passkey_credentials'], 'credential_source' => '{"private":"credential"}', 'backup_eligible' => false, 'backup_state' => false, 'display_name' => 'Private device']);
        DB::table('site_management_principals')->insert(['id' => $ids['site_management_principals'], 'identity_id' => $ids['identities']]);

        return $ids;
    }

    /** @param array<string, string> $subject
     * @return array{contact: string, reply: string}
     */
    private function createSiteHistory(array $subject): array
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        DB::table('site_management_principal_group_memberships')->insert(['principal_id' => $subject['site_management_principals'], 'principal_group_id' => SiteManagementAuthorizationSeeder::GENERAL_GROUP]);
        $contact = StrTestHelper::generateUuid();
        $reply = StrTestHelper::generateUuid();
        DB::table('contacts')->insert(['id' => $contact, 'identity_identifier' => $subject['identities'], 'category' => 1, 'name' => 'History', 'email' => 'encrypted', 'content' => 'contact history', 'language' => 'ja']);
        DB::table('contact_replies')->insert(['id' => $reply, 'contact_id' => $contact, 'identity_identifier' => $subject['identities'], 'content' => 'reply history', 'to_email' => 'encrypted']);

        return ['contact' => $contact, 'reply' => $reply];
    }

    /** @param array<string, string> $subject
     * @param array{contact: string, reply: string} $history
     */
    private function assertSiteHistory(array $subject, array $history): void
    {
        $this->assertDatabaseHas('contacts', ['id' => $history['contact'], 'identity_identifier' => $subject['identities'], 'content' => 'contact history']);
        $this->assertDatabaseHas('contact_replies', ['id' => $history['reply'], 'contact_id' => $history['contact'], 'identity_identifier' => $subject['identities'], 'content' => 'reply history']);
        $this->assertDatabaseCount('site_management_roles', 2);
        $this->assertDatabaseCount('site_management_policies', 2);
        $this->assertDatabaseCount('site_management_role_policy_attachments', 2);
        $this->assertDatabaseCount('site_management_principal_group_role_attachments', 2);
    }

    private function action(string $identityId): WithdrawFromServiceAction
    {
        $identityIdentifier = new IdentityIdentifier($identityId);
        $authentication = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $now = new DateTimeImmutable();
        $authentication->shouldReceive('requireValid')->once()->with($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION)->andReturn(new StepUpAuthentication($identityIdentifier, StepUpAuthenticationMethod::PASSKEY, $now, StepUpAuthenticationScope::RECENT_AUTHENTICATION, $now->modify('+10 minutes')));
        $this->app()->instance(StepUpAuthenticationStorageServiceInterface::class, $authentication);
        /** @var AuthServiceInterface&MockInterface $auth */
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldNotReceive('invalidateAllSessions');
        $auth->shouldNotReceive('logout');

        $this->app()->instance(AuthServiceInterface::class, $auth);

        return new WithdrawFromServiceAction($this->app()->make(WithdrawFromServiceInterface::class), new ActorContext($identityIdentifier, Language::JAPANESE), new NullLogger(), $this->app()->make(Request::class));
    }
}
