<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Service\IdentityWithdrawalServiceInterface;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class IdentityWithdrawalServiceTest extends TestCase
{
    public function testArchivesAllPrincipalsAndOnlyDeletesIndividualAccounts(): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$individual, $individualPrincipal] = $this->createMembership($identityIdentifier, 'individual');
        [$corporation, $corporatePrincipal] = $this->createMembership($identityIdentifier, 'corporation');
        $archivedAt = new DateTimeImmutable('2026-10-01 00:00:00');

        $this->app()->make(IdentityWithdrawalServiceInterface::class)->withdraw($identityIdentifier, $archivedAt);

        $this->assertDatabaseMissing('accounts', ['id' => $individual]);
        $this->assertDatabaseHas('accounts', ['id' => $corporation]);
        $this->assertDatabaseHas('archived_accounts', ['account_id' => $individual, 'account_category' => 'general', 'account_type' => 'individual']);
        $this->assertDatabaseMissing('archived_accounts', ['account_id' => $corporation]);
        foreach ([$individualPrincipal => $individual, $corporatePrincipal => $corporation] as $principal => $account) {
            $this->assertDatabaseHas('archived_principals', ['identity_id' => (string) $identityIdentifier, 'principal_type' => 'account', 'principal_id' => $principal, 'account_id' => $account, 'archived_at' => '2026-10-01 00:00:00']);
            $this->assertDatabaseMissing('account_principals', ['id' => $principal]);
        }
    }

    public function testChecksEveryMembershipBeforeAnyArchiveOrDeletion(): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$individual] = $this->createMembership($identityIdentifier, 'individual');
        [$agency] = $this->createMembership($identityIdentifier, 'corporation', 'agency');

        try {
            $this->app()->make(IdentityWithdrawalServiceInterface::class)->withdraw($identityIdentifier, new DateTimeImmutable());
            $this->fail('Agency membership must reject withdrawal.');
        } catch (IdentityWithdrawalNotAllowedException) {
            $this->assertDatabaseHas('accounts', ['id' => $individual]);
            $this->assertDatabaseHas('accounts', ['id' => $agency]);
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
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => $account, 'name' => 'Renamed administrators']);
        DB::table('account_roles')->insert(['id' => $role, 'account_id' => null, 'name' => 'Owner']);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
        foreach ([$principal, $otherPrincipal] as $member) {
            DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $member, 'principal_group_id' => $group]);
        }

        $this->expectException(IdentityWithdrawalNotAllowedException::class);
        $this->app()->make(IdentityWithdrawalServiceInterface::class)->withdraw($identityIdentifier, new DateTimeImmutable());
    }

    private function createIdentityArchive(): IdentityIdentifier
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        DB::table('archived_identities')->insert(['identity_id' => (string) $identityIdentifier, 'language' => 'ja', 'identity_created_at' => now(), 'archived_at' => now()]);

        return $identityIdentifier;
    }

    /** @return array{string, string} */
    private function createMembership(IdentityIdentifier $identityIdentifier, string $type, string $category = 'general'): array
    {
        $account = StrTestHelper::generateUuid();
        $principal = StrTestHelper::generateUuid();
        CreateAccount::create($account, ['type' => $type, 'category' => $category, 'name' => 'Private name']);
        DB::table('account_principals')->insert(['id' => $principal, 'identity_id' => (string) $identityIdentifier, 'account_id' => $account]);

        return [$account, $principal];
    }
}
