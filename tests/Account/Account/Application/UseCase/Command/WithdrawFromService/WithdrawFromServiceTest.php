<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\WithdrawFromService;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WithdrawFromServiceTest extends TestCase
{
    public function testArchivesAllPrincipalsAndOnlyDeletesIndividualAccounts(): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$individual, $individualPrincipal] = $this->createMembership($identityIdentifier, 'individual');
        [$corporation, $corporatePrincipal] = $this->createMembership($identityIdentifier, 'corporation');

        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());

        $this->assertDatabaseMissing('accounts', ['id' => $individual]);
        $this->assertDatabaseHas('accounts', ['id' => $corporation]);
        $this->assertDatabaseHas('archived_accounts', ['account_id' => $individual, 'account_category' => 'general', 'account_type' => 'individual']);
        $this->assertDatabaseMissing('archived_accounts', ['account_id' => $corporation]);
        foreach ([$individualPrincipal => $individual, $corporatePrincipal => $corporation] as $principal => $account) {
            $this->assertDatabaseHas('archived_principals', ['identity_id' => (string) $identityIdentifier, 'principal_type' => 'account', 'principal_id' => $principal, 'account_id' => $account]);
            $this->assertDatabaseMissing('account_principals', ['id' => $principal]);
        }
    }

    /** @return array<string, array{?string, string}> */
    public static function disallowedMemberships(): array
    {
        return [
            'agency' => ['corporation', 'agency'],
            'talent' => ['individual', 'talent'],
            'unclassified account' => [null, 'general'],
        ];
    }

    #[DataProvider('disallowedMemberships')]
    public function testChecksEveryMembershipBeforeAnyArchiveOrDeletion(?string $type, string $category): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$individual] = $this->createMembership($identityIdentifier, 'individual');
        [$disallowedAccount] = $this->createMembership($identityIdentifier, $type, $category);

        try {
            $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());
            $this->fail('Ineligible membership must reject withdrawal.');
        } catch (IdentityWithdrawalNotAllowedException) {
            $this->assertDatabaseHas('accounts', ['id' => $individual]);
            $this->assertDatabaseHas('accounts', ['id' => $disallowedAccount]);
            $this->assertDatabaseCount('archived_accounts', 0);
            $this->assertDatabaseCount('archived_principals', 0);
        }
    }

    public function testCorporateSystemOwnerIsRejectedEvenWithAnotherOwner(): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$account, $principal] = $this->createMembership($identityIdentifier, 'corporation');
        $otherIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($otherIdentity, ['email' => 'other-owner@example.com']);
        $otherPrincipal = StrTestHelper::generateUuid();
        DB::table('account_principals')->insert(['id' => $otherPrincipal, 'identity_id' => (string) $otherIdentity, 'account_id' => $account]);
        $group = StrTestHelper::generateUuid();
        $role = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => $account, 'name' => 'Renamed administrators', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_roles')->insert(['id' => $role, 'account_id' => null, 'name' => 'Owner']);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
        foreach ([$principal, $otherPrincipal] as $member) {
            DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $member, 'principal_group_id' => $group]);
        }

        $this->expectException(IdentityWithdrawalNotAllowedException::class);
        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());
    }

    /** @return array<string, array{string, bool, bool}> */
    public static function allowedOwnerMemberships(): array
    {
        return [
            'individual system Owner' => ['individual', true, true],
            'corporate custom role named Owner' => ['corporation', false, true],
            'corporate system Owner belonging to another principal' => ['corporation', true, false],
        ];
    }

    #[DataProvider('allowedOwnerMemberships')]
    public function testOnlyTheSubjectsCorporateSystemOwnerMembershipBlocksWithdrawal(string $type, bool $isSystemRole, bool $isSubjectMember): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$account, $principal] = $this->createMembership($identityIdentifier, $type);
        if (! $isSubjectMember) {
            $otherIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($otherIdentity, ['email' => 'other@example.com']);
            $principal = StrTestHelper::generateUuid();
            DB::table('account_principals')->insert(['id' => $principal, 'identity_id' => (string) $otherIdentity, 'account_id' => $account]);
        }
        $group = StrTestHelper::generateUuid();
        $role = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => $account, 'name' => 'Administrators', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_roles')->insert(['id' => $role, 'account_id' => $isSystemRole ? null : $account, 'name' => 'Owner']);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
        DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $principal, 'principal_group_id' => $group]);

        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());

        $this->assertDatabaseHas('archived_principals', ['identity_id' => (string) $identityIdentifier, 'account_id' => $account]);
        $this->assertDatabaseMissing('account_principals', ['identity_id' => (string) $identityIdentifier]);
        if ($type === 'corporation') {
            $this->assertDatabaseHas('accounts', ['id' => $account]);
        } else {
            $this->assertDatabaseMissing('accounts', ['id' => $account]);
        }
    }

    private function createIdentityArchive(): IdentityIdentifier
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        DB::table('archived_identities')->insert(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identityIdentifier, 'language' => 'ja', 'identity_created_at' => now(), 'archived_at' => now()]);

        return $identityIdentifier;
    }

    /** @return array{string, string} */
    private function createMembership(IdentityIdentifier $identityIdentifier, ?string $type, string $category = 'general'): array
    {
        $account = StrTestHelper::generateUuid();
        $principal = StrTestHelper::generateUuid();
        CreateAccount::create($account, ['type' => $type, 'category' => $category, 'name' => 'Private name', 'status' => $type === null ? 'pending' : 'active']);
        DB::table('account_principals')->insert(['id' => $principal, 'identity_id' => (string) $identityIdentifier, 'account_id' => $account]);

        return [$account, $principal];
    }
}
