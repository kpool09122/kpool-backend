<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup as WikiPrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as WikiPrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface as WikiPrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountOwnerPrincipalSelectionTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function accountTypes(): array
    {
        return ['individual' => ['individual'], 'corporation' => ['corporation']];
    }

    #[Group('useDb')]
    #[DataProvider('accountTypes')]
    public function testSelectsOnlySystemOwnersOfTargetAccountWithoutDuplicates(string $accountType): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account, ['type' => $accountType]);
        CreateAccount::create((string) $otherAccount);
        $systemRole = $this->role(null);
        $customRole = $this->role((string) $account);
        $ownerGroup = $this->group($account, $systemRole);
        $secondOwnerGroup = $this->group($account, $systemRole);
        $customGroup = $this->group($account, $customRole);
        $foreignGroup = $this->group($otherAccount, $systemRole);
        $owner = $this->principal($account, $ownerGroup);
        $ownerWithoutWiki = $this->principal($account, $ownerGroup);
        $nonOwner = $this->principal($account, $customGroup);
        $foreignMember = $this->principal($account, $foreignGroup);
        $this->membership($secondOwnerGroup, $owner['principal']);
        $wikiOwner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($wikiOwner, $owner['identity'], $account);
        CreatePrincipal::create(new PrincipalIdentifier(StrTestHelper::generateUuid()), $nonOwner['identity'], $account);
        CreatePrincipal::create(new PrincipalIdentifier(StrTestHelper::generateUuid()), $foreignMember['identity'], $account);
        CreatePrincipal::create(new PrincipalIdentifier(StrTestHelper::generateUuid()), $owner['identity'], $otherAccount);

        $owners = $this->app()->make(AccountPrincipalRepositoryInterface::class)->findOwnersByAccountId($account);
        $this->assertEqualsCanonicalizing(
            [$owner['principal'], $ownerWithoutWiki['principal']],
            array_map(static fn (AccountPrincipal $principal): string => (string) $principal->principalIdentifier(), $owners),
        );
        $wikiOwners = $this->app()->make(WikiPrincipalRepositoryInterface::class)->findAccountOwnersByAccountId($account);
        $this->assertCount(1, $wikiOwners);
        $this->assertSame((string) $wikiOwner, (string) $wikiOwners[0]->principalIdentifier());
    }

    #[Group('useDb')]
    public function testLateOwnerGroupsExcludeOperatorEarnedRolesAndOtherAccounts(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        CreateAccount::create((string) $otherAccount);
        $expectedIds = [];
        $roleIds = [];
        foreach (['AgencyActor', 'TalentActor', 'Operator', 'SeniorCollaborator', 'Administrator'] as $name) {
            $roleId = StrTestHelper::generateUuid();
            $roleIds[$name] = $roleId;
            DB::table('wiki_roles')->insert(['id' => $roleId, 'account_id' => null, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            $groupId = $this->wikiGroup($account, $roleId);
            if (in_array($name, ['AgencyActor', 'TalentActor'], true)) {
                $expectedIds[] = $groupId;
            }
            $this->wikiGroup($otherAccount, $roleId);
        }
        $mixedGroupId = $this->wikiGroup($account, $roleIds['TalentActor']);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => $mixedGroupId, 'role_id' => $roleIds['Operator']]);
        $customRoleId = StrTestHelper::generateUuid();
        DB::table('wiki_roles')->insert(['id' => $customRoleId, 'account_id' => (string) $account, 'name' => 'TalentActor', 'created_at' => now(), 'updated_at' => now()]);
        $this->wikiGroup($account, $customRoleId);
        $affiliationGroupId = $this->wikiGroup($account, null);
        $expectedIds[] = $affiliationGroupId;
        DB::table('affiliation_grants')->insert([
            'id' => StrTestHelper::generateUuid(), 'affiliation_id' => StrTestHelper::generateUuid(),
            'policy_id' => StrTestHelper::generateUuid(), 'role_id' => StrTestHelper::generateUuid(),
            'principal_group_id' => $affiliationGroupId, 'type' => 'talent_side', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $groups = $this->app()->make(WikiPrincipalGroupRepositoryInterface::class)->findInitialOwnerGroupsByAccountId($account);
        $this->assertEqualsCanonicalizing($expectedIds, array_map(static fn (WikiPrincipalGroup $group): string => (string) $group->principalGroupIdentifier(), $groups));
    }

    private function wikiGroup(AccountIdentifier $accountIdentifier, ?string $roleId): string
    {
        $id = StrTestHelper::generateUuid();
        DB::table('wiki_principal_groups')->insert(['id' => $id, 'account_id' => (string) $accountIdentifier, 'name' => $id, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()]);
        if ($roleId !== null) {
            DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => $id, 'role_id' => $roleId]);
        }

        return $id;
    }

    private function role(?string $accountId): string
    {
        $id = StrTestHelper::generateUuid();
        DB::table('account_roles')->insert(['id' => $id, 'account_id' => $accountId, 'name' => 'Owner', 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    private function group(AccountIdentifier $accountIdentifier, string $roleId): string
    {
        $id = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $id, 'account_id' => (string) $accountIdentifier, 'name' => $id, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $id, 'role_id' => $roleId]);

        return $id;
    }

    /** @return array{principal: string, identity: IdentityIdentifier} */
    private function principal(AccountIdentifier $accountIdentifier, string $groupId): array
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        $id = StrTestHelper::generateUuid();
        DB::table('account_principals')->insert(['id' => $id, 'identity_id' => (string) $identity, 'account_id' => (string) $accountIdentifier, 'created_at' => now(), 'updated_at' => now()]);
        $this->membership($groupId, $id);

        return ['principal' => $id, 'identity' => $identity];
    }

    private function membership(string $groupId, string $principalId): void
    {
        DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_group_id' => $groupId, 'principal_id' => $principalId, 'created_at' => now(), 'updated_at' => now()]);
    }
}
