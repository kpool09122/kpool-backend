<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal;

use Database\Seeders\AccountAuthorizationSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SiteManagementAuthorizationSeeder;
use Database\Seeders\TestAccountSeeder;
use Database\Seeders\WikiEditorSampleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountOutput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Principal\Domain\Event\PrincipalCreated;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\EventHandler\IdentityWithdrawingHandler;
use Source\SiteManagement\Principal\Application\EventHandler\PrincipalCreatedHandler;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalRepository;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        $this->app()->make(AccountAuthorizationSeeder::class)->run();
    }

    public function testRegistrationSynchronouslyProvisionsGeneralAndWithdrawalDeletesMemberships(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email((string) $identity.'@example.com');
        CreateIdentity::create($identity, ['email' => (string) $email]);
        DB::transaction(function () use ($identity, $email): void {
            $this->app()->make(CreateAccountInterface::class)->process(new CreateAccountInput($email, new AccountName('Registration'), $identity), new CreateAccountOutput());
            $this->assertDatabaseHas('site_management_principals', ['identity_id' => (string) $identity]);
        });
        $id = DB::table('site_management_principals')->where('identity_id', (string) $identity)->value('id');
        $this->assertSame(1, DB::table('site_management_principal_group_memberships')->where('principal_id', $id)->count());
        $this->app()->make(IdentityWithdrawingHandler::class)->handle(new IdentityWithdrawing($identity));
        $this->assertDatabaseMissing('site_management_principals', ['id' => $id]);
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_id' => $id]);
        $this->assertDatabaseCount('site_management_roles', 2);
        $this->assertDatabaseCount('site_management_policies', 2);
    }

    public function testRegistrationListenerFailureRollsBackBothPrincipalsAndAccount(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email((string) $identity.'@example.com');
        CreateIdentity::create($identity, ['email' => (string) $email]);
        Event::listen(PrincipalCreated::class, function () use ($identity): void {
            $this->assertDatabaseHas('site_management_principals', ['identity_id' => (string) $identity]);

            throw new RuntimeException('registration failed');
        });

        try {
            DB::transaction(fn () => $this->app()->make(CreateAccountInterface::class)->process(new CreateAccountInput($email, new AccountName('Rollback'), $identity), new CreateAccountOutput()));
            $this->fail('Expected listener failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('registration failed', $exception->getMessage());
        }
        $this->assertDatabaseMissing('accounts', ['email' => (string) $email]);
        $this->assertDatabaseMissing('account_principals', ['identity_id' => (string) $identity]);
        $this->assertDatabaseMissing('site_management_principals', ['identity_id' => (string) $identity]);
    }

    public function testProvisioningFailureRollsBackPrincipalAndGroupChangesInHandler(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $memberships = DB::table('site_management_principal_group_memberships')->orderBy('principal_id')->get()->all();
        $attachments = DB::table('site_management_principal_group_role_attachments')->orderBy('role_id')->get()->all();
        $principalGroupRepository = new PrincipalGroupRepository();
        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findDefaultByAccountIdentifier')->once()
            ->with(Mockery::type(AccountIdentifier::class))
            ->andReturnUsing($principalGroupRepository->findDefaultByAccountIdentifier(...));
        $repository->shouldReceive('save')->once()
            ->with(Mockery::type(PrincipalGroup::class))
            ->andReturnUsing(function (PrincipalGroup $group) use ($principalGroupRepository): void {
                $principalGroupRepository->save($group);
                $this->assertDatabaseCount('site_management_principal_group_memberships', 1);

                throw new RuntimeException('group save failed');
            });
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $event = new PrincipalCreated(
            new AccountPrincipalIdentifier(StrTestHelper::generateUuid()),
            $identity,
            $accountIdentifier,
        );

        try {
            $this->app()->make(PrincipalCreatedHandler::class)->handle($event);
            $this->fail('Expected provisioning failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('group save failed', $exception->getMessage());
        }

        $this->assertDatabaseMissing('site_management_principals', ['identity_id' => (string) $identity]);
        $this->assertEquals($memberships, DB::table('site_management_principal_group_memberships')->orderBy('principal_id')->get()->all());
        $this->assertEquals($attachments, DB::table('site_management_principal_group_role_attachments')->orderBy('role_id')->get()->all());
    }

    public function testWithdrawalFailureRestoresPrincipalAndMembershipsInHandler(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $this->app()->make(PrincipalCreatedHandler::class)->handle(new PrincipalCreated(
            new AccountPrincipalIdentifier(StrTestHelper::generateUuid()),
            $identity,
            $accountIdentifier,
        ));
        $principalRepository = new PrincipalRepository();
        $principal = $principalRepository->findByIdentityIdentifierAndAccountIdentifier($identity, $accountIdentifier);
        $this->assertNotNull($principal);
        $repository = Mockery::mock(PrincipalRepositoryInterface::class);
        $repository->shouldReceive('findAllByIdentityIdentifier')->once()->with($identity)->andReturn([$principal]);
        $repository->shouldReceive('delete')->once()->with($principal)
            ->andReturnUsing(function (Principal $principal) use ($principalRepository): void {
                $principalRepository->delete($principal);
                $this->assertDatabaseMissing('site_management_principals', ['id' => (string) $principal->principalIdentifier()]);
                $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_id' => (string) $principal->principalIdentifier()]);

                throw new RuntimeException('withdrawal failed');
            });
        $this->app()->instance(PrincipalRepositoryInterface::class, $repository);

        try {
            $this->app()->make(IdentityWithdrawingHandler::class)->handle(new IdentityWithdrawing($identity));
            $this->fail('Expected withdrawal failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('withdrawal failed', $exception->getMessage());
        }

        $this->assertDatabaseHas('site_management_principals', ['id' => (string) $principal->principalIdentifier()]);
        $this->assertDatabaseHas('site_management_principal_group_memberships', ['principal_id' => (string) $principal->principalIdentifier()]);
    }

    public function testSeedersProvisionEveryAccountPrincipalAsGeneralAndAreIdempotent(): void
    {
        $seeder = $this->app()->make(DatabaseSeeder::class);
        $seeder->setContainer($this->app());
        $seeder->run();
        $first = DB::table('site_management_principals')->orderBy('id')->pluck('id')->all();
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();
        $this->app()->make(TestAccountSeeder::class)->run();
        $this->app()->make(WikiEditorSampleSeeder::class)->run();
        $this->assertSame($first, DB::table('site_management_principals')->orderBy('id')->pluck('id')->all());
        $this->assertSame(DB::table('account_principals')->count(), count($first));
        $this->assertSame(count($first), DB::table('site_management_principal_group_memberships')->join('site_management_principal_groups as groups', 'groups.id', '=', 'site_management_principal_group_memberships.principal_group_id')->where('groups.is_default', true)->count());
        $this->assertSame(0, DB::table('site_management_principal_group_memberships')->join('site_management_principal_groups as groups', 'groups.id', '=', 'site_management_principal_group_memberships.principal_group_id')->where('groups.name', 'Operator')->count());
    }
}
