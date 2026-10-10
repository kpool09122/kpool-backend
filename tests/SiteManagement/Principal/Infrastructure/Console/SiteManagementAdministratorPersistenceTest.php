<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Console;

use Application\Console\Commands\GrantSiteManagementAdministratorCommand;
use Application\Console\Commands\RevokeSiteManagementAdministratorCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as AccountPrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\SiteManagementAdministratorTestData;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class SiteManagementAdministratorPersistenceTest extends TestCase
{
    private ?SiteManagementAdministratorTestData $data = null;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::registerCommand(new GrantSiteManagementAdministratorCommand());
        Artisan::registerCommand(new RevokeSiteManagementAdministratorCommand());
        $this->data = SiteManagementAdministratorTestData::create();
        CreateAccount::create((string) $this->data()->account->accountIdentifier(), ['email' => (string) $this->data()->email]);
        CreateIdentity::create($this->data()->identity->identityIdentifier(), ['email' => (string) $this->data()->email]);
        $this->app()->make(AccountPrincipalRepositoryInterface::class)->save($this->data()->accountPrincipal);
        $this->app()->make(AccountRoleRepositoryInterface::class)->save($this->data()->operationsRole);
        $this->app()->make(AccountPrincipalGroupRepositoryInterface::class)->save($this->data()->operationsGroup);
        $this->app()->make(PrincipalRepositoryInterface::class)->save($this->data()->siteManagementPrincipal);
        $this->app()->make(RoleRepositoryInterface::class)->save($this->data()->administratorRole);
    }

    public function testRepeatedGrantReusesGroupAndPreservesExistingRolesAndMembers(): void
    {
        $otherMember = $this->createMember($this->data()->account->accountIdentifier());
        $otherRole = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'other', [], $this->data()->account->accountIdentifier());
        $this->app()->make(RoleRepositoryInterface::class)->save($otherRole);
        $group = $this->data()->administratorGroup;
        $group->addRole($otherRole);
        $group->addMember($otherMember->principalIdentifier());
        $repository = $this->app()->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($group);

        $this->assertSame(0, $this->grant());
        $this->assertSame(0, $this->grant());
        $loaded = $repository->findByAccountIdAndName($this->data()->account->accountIdentifier(), 'Operations SiteManagement Administrators');
        $this->assertNotNull($loaded);
        $this->assertSame((string) $group->principalGroupIdentifier(), (string) $loaded->principalGroupIdentifier());
        $this->assertFalse($loaded->isDefault());
        $this->assertCount(2, $loaded->members());
        $this->assertTrue($loaded->hasMember($otherMember->principalIdentifier()));
        $this->assertCount(2, $loaded->roles());
        $this->assertTrue($loaded->hasRole($otherRole->roleIdentifier()));
        $this->assertDatabaseCount('site_management_principal_groups', 1);
        $this->assertDatabaseCount('site_management_principal_group_memberships', 2);
        $this->assertDatabaseCount('site_management_principal_group_role_attachments', 2);
    }

    public function testRevokesAllMembersButPreservesOtherAccountsGeneralGroupsAndOperations(): void
    {
        $this->assertSame(0, $this->grant());
        $repository = $this->app()->make(PrincipalGroupRepositoryInterface::class);
        $group = $repository->findByAccountIdAndName($this->data()->account->accountIdentifier(), 'Operations SiteManagement Administrators');
        $this->assertNotNull($group);
        $group->addMember($this->createMember($this->data()->account->accountIdentifier())->principalIdentifier());
        $repository->save($group);
        $general = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'general', [$this->data()->administratorRole->roleIdentifier()], $this->data()->account->accountIdentifier());
        $general->addMember($this->data()->siteManagementPrincipal->principalIdentifier());
        $repository->save($general);
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        $other = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'Operations SiteManagement Administrators', [$this->data()->administratorRole->roleIdentifier()], $otherAccount);
        $other->addMember($this->createMember($otherAccount)->principalIdentifier());
        $repository->save($other);

        $this->assertSame(0, $this->revoke());
        $this->assertNull($repository->findById($group->principalGroupIdentifier()));
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_group_id' => (string) $group->principalGroupIdentifier()]);
        $this->assertDatabaseMissing('site_management_principal_group_role_attachments', ['principal_group_id' => (string) $group->principalGroupIdentifier()]);
        $this->assertNotNull($repository->findById($general->principalGroupIdentifier()));
        $this->assertNotNull($repository->findById($other->principalGroupIdentifier()));
        $this->assertDatabaseCount('site_management_principal_group_memberships', 2);
        $this->assertDatabaseCount('site_management_principal_group_role_attachments', 2);
        $this->assertNotNull($this->app()->make(AccountPrincipalGroupRepositoryInterface::class)->findById($this->data()->operationsGroup->principalGroupIdentifier()));
        $this->assertSame(1, $this->revoke());
    }

    public function testRevokesLastAdministratorWithoutIdentityPrincipalOrOperations(): void
    {
        $this->assertSame(0, $this->grant());
        $this->app()->make(AccountPrincipalGroupRepositoryInterface::class)->delete($this->data()->operationsGroup);
        DB::table('identities')->where('id', (string) $this->data()->identity->identityIdentifier())->delete();
        $this->assertSame(0, $this->revoke());
        $this->assertDatabaseCount('site_management_principal_groups', 0);
        $this->assertDatabaseCount('site_management_principal_group_memberships', 0);
        $this->assertDatabaseCount('site_management_principal_group_role_attachments', 0);
    }

    public function testRevokesLastRemainingMember(): void
    {
        $this->assertSame(0, $this->grant());
        $this->assertSame(0, $this->revoke());
        $this->assertDatabaseCount('site_management_principal_groups', 0);
        $this->assertDatabaseCount('site_management_principal_group_memberships', 0);
        $this->assertDatabaseCount('site_management_principal_group_role_attachments', 0);
    }

    public function testMissingOperationsMembershipLeavesAdministratorGroupUnchanged(): void
    {
        $this->app()->make(AccountPrincipalGroupRepositoryInterface::class)->delete($this->data()->operationsGroup);
        $this->assertSame(1, $this->grant());
        $this->assertDatabaseCount('site_management_principal_groups', 0);
    }

    public function testMissingSiteManagementPrincipalDoesNotProvisionOrWriteGroup(): void
    {
        $this->app()->make(PrincipalRepositoryInterface::class)->delete($this->data()->siteManagementPrincipal);
        $this->assertSame(1, $this->grant());
        $this->assertDatabaseCount('site_management_principals', 0);
        $this->assertDatabaseCount('site_management_principal_groups', 0);
    }

    /** @return array<string, array{string}> */
    public static function defaultGroupOperations(): array
    {
        return ['grant' => ['grant'], 'revoke' => ['revoke']];
    }

    #[DataProvider('defaultGroupOperations')]
    public function testDoesNotModifyDefaultGroupWithReservedName(string $operation): void
    {
        $group = new PrincipalGroup(
            $this->data()->administratorGroup->principalGroupIdentifier(),
            'Operations SiteManagement Administrators',
            [$this->data()->administratorRole->roleIdentifier()],
            $this->data()->account->accountIdentifier(),
            true,
        );
        $group->addMember($this->data()->siteManagementPrincipal->principalIdentifier());
        $repository = $this->app()->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($group);

        $this->assertSame(1, Artisan::call('site-management:administrator:' . $operation, ['email' => (string) $this->data()->email]));
        $loaded = $repository->findById($group->principalGroupIdentifier());
        $this->assertNotNull($loaded);
        $this->assertTrue($loaded->isDefault());
        $this->assertCount(1, $loaded->roles());
        $this->assertCount(1, $loaded->members());
    }

    public function testUnattachedAdministratorRolePreventsDeletion(): void
    {
        $repository = $this->app()->make(PrincipalGroupRepositoryInterface::class);
        $repository->save($this->data()->administratorGroup);
        $this->assertSame(1, $this->revoke());
        $this->assertNotNull($repository->findById($this->data()->administratorGroup->principalGroupIdentifier()));
    }

    private function data(): SiteManagementAdministratorTestData
    {
        return $this->data ?? throw new LogicException('Test data is not initialized.');
    }

    private function grant(): int
    {
        return Artisan::call('site-management:administrator:grant', ['email' => (string) $this->data()->email]);
    }

    private function revoke(): int
    {
        return Artisan::call('site-management:administrator:revoke', ['email' => (string) $this->data()->email]);
    }

    private function createMember(AccountIdentifier $accountIdentifier): Principal
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, ['email' => (string) $identityIdentifier . '@example.com']);
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identityIdentifier, $accountIdentifier);
        $this->app()->make(PrincipalRepositoryInterface::class)->save($principal);

        return $principal;
    }
}
