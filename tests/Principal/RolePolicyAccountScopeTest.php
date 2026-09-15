<?php

declare(strict_types=1);

namespace Tests\Principal;

use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Policy as AccountPolicy;
use Source\Account\Principal\Domain\Entity\PrincipalGroup as AccountPrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role as AccountRole;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface as AccountPolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier as AccountPolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier as AccountRoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier as AccountPrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\Policy as WikiPolicy;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup as WikiPrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role as WikiRole;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface as WikiPolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface as WikiRoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier as WikiPolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier as WikiPrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier as WikiRoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RolePolicyAccountScopeTest extends TestCase
{
    #[Group('useDb')]
    public function testAccountRepositoriesFindSystemAndAccountLocalDefinitionsByName(): void
    {
        $accountA = $this->accountIdentifier();
        $accountB = $this->accountIdentifier();
        $policyRepository = $this->app->make(AccountPolicyRepositoryInterface::class);
        $roleRepository = $this->app->make(AccountRoleRepositoryInterface::class);

        $systemPolicy = new AccountPolicy($this->accountPolicyIdentifier(), 'Shared Policy', [], null, new DateTimeImmutable());
        $accountPolicyA = new AccountPolicy($this->accountPolicyIdentifier(), 'Shared Policy', [], $accountA, new DateTimeImmutable());
        $accountPolicyB = new AccountPolicy($this->accountPolicyIdentifier(), 'Shared Policy', [], $accountB, new DateTimeImmutable());
        $policyRepository->save($systemPolicy);
        $policyRepository->save($accountPolicyA);
        $policyRepository->save($accountPolicyB);

        $systemRole = new AccountRole($this->accountRoleIdentifier(), 'Shared Role', [], null);
        $accountRoleA = new AccountRole($this->accountRoleIdentifier(), 'Shared Role', [], $accountA);
        $accountRoleB = new AccountRole($this->accountRoleIdentifier(), 'Shared Role', [], $accountB);
        $roleRepository->save($systemRole);
        $roleRepository->save($accountRoleA);
        $roleRepository->save($accountRoleB);

        $this->assertSame((string) $systemPolicy->policyIdentifier(), (string) $policyRepository->findSystemByName('Shared Policy')?->policyIdentifier());
        $this->assertSame((string) $accountPolicyA->policyIdentifier(), (string) $policyRepository->findByAccountIdAndName($accountA, 'Shared Policy')?->policyIdentifier());
        $this->assertSame((string) $accountPolicyB->policyIdentifier(), (string) $policyRepository->findByAccountIdAndName($accountB, 'Shared Policy')?->policyIdentifier());
        $this->assertSame((string) $systemRole->roleIdentifier(), (string) $roleRepository->findSystemByName('Shared Role')?->roleIdentifier());
        $this->assertSame((string) $accountRoleA->roleIdentifier(), (string) $roleRepository->findByAccountIdAndName($accountA, 'Shared Role')?->roleIdentifier());
        $this->assertSame((string) $accountRoleB->roleIdentifier(), (string) $roleRepository->findByAccountIdAndName($accountB, 'Shared Role')?->roleIdentifier());
    }

    #[Group('useDb')]
    public function testWikiRepositoriesFindSystemAndAccountLocalDefinitionsByName(): void
    {
        $accountA = $this->accountIdentifier();
        $accountB = $this->accountIdentifier();
        $policyRepository = $this->app->make(WikiPolicyRepositoryInterface::class);
        $roleRepository = $this->app->make(WikiRoleRepositoryInterface::class);

        $systemPolicy = new WikiPolicy($this->wikiPolicyIdentifier(), 'Shared Policy', [], null, new DateTimeImmutable());
        $accountPolicyA = new WikiPolicy($this->wikiPolicyIdentifier(), 'Shared Policy', [], $accountA, new DateTimeImmutable());
        $accountPolicyB = new WikiPolicy($this->wikiPolicyIdentifier(), 'Shared Policy', [], $accountB, new DateTimeImmutable());
        $policyRepository->save($systemPolicy);
        $policyRepository->save($accountPolicyA);
        $policyRepository->save($accountPolicyB);

        $systemRole = new WikiRole($this->wikiRoleIdentifier(), 'Shared Role', [], null, new DateTimeImmutable());
        $accountRoleA = new WikiRole($this->wikiRoleIdentifier(), 'Shared Role', [], $accountA, new DateTimeImmutable());
        $accountRoleB = new WikiRole($this->wikiRoleIdentifier(), 'Shared Role', [], $accountB, new DateTimeImmutable());
        $roleRepository->save($systemRole);
        $roleRepository->save($accountRoleA);
        $roleRepository->save($accountRoleB);

        $this->assertSame((string) $systemPolicy->policyIdentifier(), (string) $policyRepository->findSystemByName('Shared Policy')?->policyIdentifier());
        $this->assertSame((string) $accountPolicyA->policyIdentifier(), (string) $policyRepository->findByAccountIdAndName($accountA, 'Shared Policy')?->policyIdentifier());
        $this->assertSame((string) $accountPolicyB->policyIdentifier(), (string) $policyRepository->findByAccountIdAndName($accountB, 'Shared Policy')?->policyIdentifier());
        $this->assertSame((string) $systemRole->roleIdentifier(), (string) $roleRepository->findSystemByName('Shared Role')?->roleIdentifier());
        $this->assertSame((string) $accountRoleA->roleIdentifier(), (string) $roleRepository->findByAccountIdAndName($accountA, 'Shared Role')?->roleIdentifier());
        $this->assertSame((string) $accountRoleB->roleIdentifier(), (string) $roleRepository->findByAccountIdAndName($accountB, 'Shared Role')?->roleIdentifier());
    }

    public function testAccountScopeRules(): void
    {
        $accountA = $this->accountIdentifier();
        $accountB = $this->accountIdentifier();
        $globalPolicy = new AccountPolicy($this->accountPolicyIdentifier(), 'Global', [], null, new DateTimeImmutable());
        $policyA = new AccountPolicy($this->accountPolicyIdentifier(), 'A', [], $accountA, new DateTimeImmutable());
        $policyB = new AccountPolicy($this->accountPolicyIdentifier(), 'B', [], $accountB, new DateTimeImmutable());
        $globalRole = new AccountRole($this->accountRoleIdentifier(), 'Global', [], null);
        $roleA = new AccountRole($this->accountRoleIdentifier(), 'A', [], $accountA);
        $roleB = new AccountRole($this->accountRoleIdentifier(), 'B', [], $accountB);
        $groupA = new AccountPrincipalGroup($this->accountPrincipalGroupIdentifier(), $accountA, 'A', false, new DateTimeImmutable());

        $this->assertTrue($globalRole->canAttachPolicy($globalPolicy));
        $this->assertFalse($globalRole->canAttachPolicy($policyA));
        $this->assertTrue($roleA->canAttachPolicy($globalPolicy));
        $this->assertTrue($roleA->canAttachPolicy($policyA));
        $this->assertFalse($roleA->canAttachPolicy($policyB));
        $this->assertTrue($groupA->canAttachRole($globalRole));
        $this->assertTrue($groupA->canAttachRole($roleA));
        $this->assertFalse($groupA->canAttachRole($roleB));
    }

    public function testWikiScopeRules(): void
    {
        $accountA = $this->accountIdentifier();
        $accountB = $this->accountIdentifier();
        $globalPolicy = new WikiPolicy($this->wikiPolicyIdentifier(), 'Global', [], null, new DateTimeImmutable());
        $policyA = new WikiPolicy($this->wikiPolicyIdentifier(), 'A', [], $accountA, new DateTimeImmutable());
        $policyB = new WikiPolicy($this->wikiPolicyIdentifier(), 'B', [], $accountB, new DateTimeImmutable());
        $globalRole = new WikiRole($this->wikiRoleIdentifier(), 'Global', [], null, new DateTimeImmutable());
        $roleA = new WikiRole($this->wikiRoleIdentifier(), 'A', [], $accountA, new DateTimeImmutable());
        $roleB = new WikiRole($this->wikiRoleIdentifier(), 'B', [], $accountB, new DateTimeImmutable());
        $groupA = new WikiPrincipalGroup($this->wikiPrincipalGroupIdentifier(), $accountA, 'A', false, new DateTimeImmutable());

        $this->assertTrue($globalRole->canAttachPolicy($globalPolicy));
        $this->assertFalse($globalRole->canAttachPolicy($policyA));
        $this->assertTrue($roleA->canAttachPolicy($globalPolicy));
        $this->assertTrue($roleA->canAttachPolicy($policyA));
        $this->assertFalse($roleA->canAttachPolicy($policyB));
        $this->assertTrue($groupA->canAttachRole($globalRole));
        $this->assertTrue($groupA->canAttachRole($roleA));
        $this->assertFalse($groupA->canAttachRole($roleB));
    }

    #[Group('useDb')]
    public function testAccountRoleRepositoryRejectsIncompatiblePolicyBeforePersistingRole(): void
    {
        $policyRepository = $this->app->make(AccountPolicyRepositoryInterface::class);
        $roleRepository = $this->app->make(AccountRoleRepositoryInterface::class);
        $policy = new AccountPolicy(
            $this->accountPolicyIdentifier(),
            'Account-local policy',
            [],
            $this->accountIdentifier(),
            new DateTimeImmutable(),
        );
        $policyRepository->save($policy);
        $role = new AccountRole($this->accountRoleIdentifier(), 'Global role', [$policy->policyIdentifier()], null);

        try {
            $roleRepository->save($role);
            $this->fail('Expected incompatible scope to be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseMissing('account_roles', ['id' => (string) $role->roleIdentifier()]);
        }
    }

    #[Group('useDb')]
    public function testWikiRoleRepositoryRejectsCrossAccountPolicyBeforePersistingRole(): void
    {
        $policyRepository = $this->app->make(WikiPolicyRepositoryInterface::class);
        $roleRepository = $this->app->make(WikiRoleRepositoryInterface::class);
        $policy = new WikiPolicy(
            $this->wikiPolicyIdentifier(),
            'Account B policy',
            [],
            $this->accountIdentifier(),
            new DateTimeImmutable(),
        );
        $policyRepository->save($policy);
        $role = new WikiRole(
            $this->wikiRoleIdentifier(),
            'Account A role',
            [$policy->policyIdentifier()],
            $this->accountIdentifier(),
            new DateTimeImmutable(),
        );

        try {
            $roleRepository->save($role);
            $this->fail('Expected incompatible scope to be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseMissing('wiki_roles', ['id' => (string) $role->roleIdentifier()]);
        }
    }

    #[Group('useDb')]
    #[DataProvider('uniqueNameScopeProvider')]
    public function testNamesAreUniqueWithinSystemOrSameAccountScope(string $table, bool $accountLocal): void
    {
        $accountId = $accountLocal ? (string) $this->accountIdentifier() : null;
        $name = 'Duplicate ' . StrTestHelper::generateUuid();
        $record = [
            'id' => StrTestHelper::generateUuid(),
            'account_id' => $accountId,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (str_contains($table, 'policies')) {
            $record['statements'] = '[]';
        }

        DB::table($table)->insert($record);
        $record['id'] = StrTestHelper::generateUuid();

        $this->expectException(QueryException::class);
        DB::table($table)->insert($record);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function uniqueNameScopeProvider(): iterable
    {
        foreach (['account_roles', 'account_policies', 'wiki_roles', 'wiki_policies'] as $table) {
            yield $table . '-system' => [$table, false];
            yield $table . '-account-local' => [$table, true];
        }
    }

    private function accountIdentifier(): AccountIdentifier
    {
        return new AccountIdentifier(StrTestHelper::generateUuid());
    }

    private function accountPolicyIdentifier(): AccountPolicyIdentifier
    {
        return new AccountPolicyIdentifier(StrTestHelper::generateUuid());
    }

    private function accountRoleIdentifier(): AccountRoleIdentifier
    {
        return new AccountRoleIdentifier(StrTestHelper::generateUuid());
    }

    private function accountPrincipalGroupIdentifier(): AccountPrincipalGroupIdentifier
    {
        return new AccountPrincipalGroupIdentifier(StrTestHelper::generateUuid());
    }

    private function wikiPolicyIdentifier(): WikiPolicyIdentifier
    {
        return new WikiPolicyIdentifier(StrTestHelper::generateUuid());
    }

    private function wikiRoleIdentifier(): WikiRoleIdentifier
    {
        return new WikiRoleIdentifier(StrTestHelper::generateUuid());
    }

    private function wikiPrincipalGroupIdentifier(): WikiPrincipalGroupIdentifier
    {
        return new WikiPrincipalGroupIdentifier(StrTestHelper::generateUuid());
    }
}
