<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Database\Seeders\MonetizationAuthorizationSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Service\ChildPrincipalManagerContinuityInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus as AccountPrincipalStatus;
use Source\Monetization\Principal\Application\Exception\CannotRemoveLastMonetizationAdministratorException;
use Source\Monetization\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Monetization\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalStatusUpdate;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInterface;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\Monetization\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;
use Throwable;

#[Group('useDb')]
class UpdatePrincipalGroupMembersTest extends TestCase
{
    public function testTransfersManagementAndSuspendsPreviousManagerInOneUpdate(): void
    {
        [$account, $old, $next, $group] = $this->fixture();
        $output = new UpdatePrincipalGroupMembersOutput();
        DB::transaction(fn () => $this->app()->make(UpdatePrincipalGroupMembersInterface::class)->process(
            new UpdatePrincipalGroupMembersInput($account, $old, [new PrincipalGroupMembers($group, [$next])], [new PrincipalStatusUpdate($old, PrincipalStatus::SUSPENDED)]),
            $output,
        ));
        self::assertSame([(string) $next], DB::table('monetization_principal_group_memberships')->pluck('principal_id')->all());
        self::assertSame('suspended', DB::table('monetization_principals')->where('id', (string) $old)->value('status'));
        self::assertSame('active', DB::table('monetization_principals')->where('id', (string) $next)->value('status'));
        self::assertSame([['principalIdentifier' => (string) $old, 'status' => 'suspended']], $output->toArray()['principalStatuses']);
    }

    /** @return array{AccountIdentifier, PrincipalIdentifier, PrincipalIdentifier, PrincipalGroupIdentifier} */
    private function fixture(): array
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        $old = $this->member($account);
        $next = $this->member($account);
        $group = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        DB::table('monetization_principal_groups')->insert(['id' => (string) $group, 'account_id' => (string) $account, 'name' => 'Managers', 'is_default' => false]);
        DB::table('monetization_principal_group_memberships')->insert(['principal_group_id' => (string) $group, 'principal_id' => (string) $old]);
        DB::table('monetization_principal_group_role_attachments')->insert(['principal_group_id' => (string) $group, 'role_id' => '09290000-0000-7000-8000-000000000003']);

        return [$account, $old, $next, $group];
    }

    public function testStatusOnlyUpdatePreservesUnspecifiedGroupsAndPrincipals(): void
    {
        [$account, $old, $next, $group] = $this->fixture();
        $output = new UpdatePrincipalGroupMembersOutput();
        $this->app()->make(UpdatePrincipalGroupMembersInterface::class)->process(new UpdatePrincipalGroupMembersInput($account, $old, [], [new PrincipalStatusUpdate($next, PrincipalStatus::SUSPENDED)]), $output);
        self::assertSame([(string) $old], DB::table('monetization_principal_group_memberships')->pluck('principal_id')->all());
        self::assertSame('active', DB::table('monetization_principals')->where('id', (string) $old)->value('status'));
        self::assertSame([], $output->toArray()['principalGroups']);
        self::assertSame('suspended', DB::table('monetization_principals')->where('id', (string) $next)->value('status'));
    }

    /** @return array<string, array{string, class-string<Throwable>}> */
    public static function rejectedChanges(): array
    {
        return [
            'remove last manager' => ['remove', CannotRemoveLastMonetizationAdministratorException::class],
            'suspend last manager' => ['suspend', CannotRemoveLastMonetizationAdministratorException::class],
            'unreachable successor' => ['unreachable', CannotRemoveLastMonetizationAdministratorException::class],
            'suspended successor' => ['suspended_successor', CannotRemoveLastMonetizationAdministratorException::class],
            'deny successor' => ['deny_successor', CannotRemoveLastMonetizationAdministratorException::class],
            'duplicate group' => ['duplicate_group', InvalidArgumentException::class],
            'duplicate member' => ['duplicate_member', InvalidArgumentException::class],
            'duplicate status' => ['duplicate_status', InvalidArgumentException::class],
            'missing group' => ['missing_group', PrincipalGroupNotFoundException::class],
            'missing principal' => ['missing_principal', PrincipalNotFoundException::class],
            'foreign group' => ['foreign_group', PrincipalGroupNotFoundException::class],
            'foreign principal' => ['foreign_principal', PrincipalNotFoundException::class],
            'forced stop' => ['forced_stop', PrincipalStatusChangeForbiddenException::class],
            'restore forced stop' => ['restore_forced_stop', PrincipalStatusChangeForbiddenException::class],
            'no grant' => ['no_grant', UnauthorizedException::class],
            'deny operator' => ['deny_operator', UnauthorizedException::class],
            'suspended operator' => ['suspended_operator', UnauthorizedException::class],
            'unreachable operator' => ['unreachable_operator', UnauthorizedException::class],
            'wrong effective account' => ['foreign_operator', UnauthorizedException::class],
        ];
    }

    /** @param class-string<Throwable> $exception */
    #[DataProvider('rejectedChanges')]
    public function testRejectsInvalidFinalStateWithoutAnyWrites(string $scenario, string $exception): void
    {
        [$account, $old, $next, $group] = $this->fixture();
        $groups = [new PrincipalGroupMembers($group, [$next])];
        $statuses = [];
        switch ($scenario) {
            case 'remove': $groups = [new PrincipalGroupMembers($group, [])];

                break;
            case 'suspend': $groups = [];
                $statuses = [new PrincipalStatusUpdate($old, PrincipalStatus::SUSPENDED)];

                break;
            case 'unreachable': DB::table('account_principals')->where('identity_id', DB::table('monetization_principals')->where('id', (string) $next)->value('identity_id'))->update(['status' => 'suspended']);

                break;
            case 'suspended_successor': DB::table('monetization_principals')->where('id', (string) $next)->update(['status' => 'suspended']);

                break;
            case 'deny_successor': $this->deny($account, $next);

                break;
            case 'duplicate_group': $groups[] = $groups[0];

                break;
            case 'duplicate_member': $groups = [new PrincipalGroupMembers($group, [$next, $next])];

                break;
            case 'duplicate_status': $statuses = [new PrincipalStatusUpdate($next, PrincipalStatus::SUSPENDED), new PrincipalStatusUpdate($next, PrincipalStatus::ACTIVE)];

                break;
            case 'missing_group': $groups = [new PrincipalGroupMembers(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), [])];

                break;
            case 'missing_principal': $groups = [new PrincipalGroupMembers($group, [new PrincipalIdentifier(StrTestHelper::generateUuid())])];

                break;
            case 'foreign_group':
            case 'foreign_principal':
            case 'foreign_operator':
                $other = new AccountIdentifier(StrTestHelper::generateUuid());
                CreateAccount::create((string) $other);
                if ($scenario === 'foreign_group') {
                    DB::table('monetization_principal_groups')->insert(['id' => (string) ($foreignGroup = new PrincipalGroupIdentifier(StrTestHelper::generateUuid())), 'account_id' => (string) $other, 'name' => 'Foreign', 'is_default' => false]);
                    $groups = [new PrincipalGroupMembers($foreignGroup, [])];
                } elseif ($scenario === 'foreign_operator') {
                    $account = $other;
                } else {
                    DB::table('monetization_principals')->where('id', (string) $next)->update(['account_id' => (string) $other]);
                }

                break;
            case 'forced_stop': $statuses = [new PrincipalStatusUpdate($next, PrincipalStatus::FORCED_STOP)];

                break;
            case 'restore_forced_stop': DB::table('monetization_principals')->where('id', (string) $next)->update(['status' => 'forced_stop']);
                $statuses = [new PrincipalStatusUpdate($next, PrincipalStatus::ACTIVE)];

                break;
            case 'no_grant': DB::table('monetization_principal_group_memberships')->delete();

                break;
            case 'deny_operator': $this->deny($account, $old);

                break;
            case 'suspended_operator': DB::table('monetization_principals')->where('id', (string) $old)->update(['status' => 'suspended']);

                break;
            case 'unreachable_operator': DB::table('account_principals')->update(['status' => 'suspended']);

                break;
        }
        $before = DB::table('monetization_principal_group_memberships')->orderBy('principal_id')->get()->toJson();
        $beforeStatuses = DB::table('monetization_principals')->orderBy('id')->get()->toJson();

        try {
            DB::transaction(fn () => $this->app()->make(UpdatePrincipalGroupMembersInterface::class)->process(new UpdatePrincipalGroupMembersInput($account, $old, $groups, $statuses), new UpdatePrincipalGroupMembersOutput()));
            self::fail('Invalid update must be rejected.');
        } catch (Throwable $e) {
            self::assertInstanceOf($exception, $e);
            self::assertSame($before, DB::table('monetization_principal_group_memberships')->orderBy('principal_id')->get()->toJson());
            self::assertSame($beforeStatuses, DB::table('monetization_principals')->orderBy('id')->get()->toJson());
        }
    }

    public function testSameForcedStopStatusCanBeResent(): void
    {
        [$account, $old, $next] = $this->fixture();
        DB::table('monetization_principals')->where('id', (string) $next)->update(['status' => 'forced_stop']);
        $output = new UpdatePrincipalGroupMembersOutput();
        $this->app()->make(UpdatePrincipalGroupMembersInterface::class)->process(new UpdatePrincipalGroupMembersInput($account, $old, [], [new PrincipalStatusUpdate($next, PrincipalStatus::FORCED_STOP)]), $output);
        self::assertSame('forced_stop', $output->toArray()['principalStatuses'][0]['status']);
    }

    public function testPersistenceFailureAfterMembershipWriteRollsBackEntireUpdate(): void
    {
        [$account, $old, $next, $group] = $this->fixture();
        $failure = new RuntimeException('Injected late write failure');
        $wroteGroup = false;
        DB::listen(function (QueryExecuted $query) use ($next, $failure, &$wroteGroup): void {
            if (str_starts_with($query->sql, 'update "monetization_principals"')) {
                self::assertSame([(string) $next], DB::table('monetization_principal_group_memberships')->pluck('principal_id')->all());
                $wroteGroup = true;

                throw $failure;
            }
        });

        try {
            DB::transaction(fn () => $this->app()->make(UpdatePrincipalGroupMembersInterface::class)->process(new UpdatePrincipalGroupMembersInput($account, $old, [new PrincipalGroupMembers($group, [$next])], [new PrincipalStatusUpdate($old, PrincipalStatus::SUSPENDED)]), new UpdatePrincipalGroupMembersOutput()));
            self::fail('Injected failure must propagate.');
        } catch (RuntimeException $e) {
            self::assertSame($failure, $e);
            self::assertTrue($wroteGroup);
            self::assertSame([(string) $old], DB::table('monetization_principal_group_memberships')->pluck('principal_id')->all());
            self::assertSame('active', DB::table('monetization_principals')->where('id', (string) $old)->value('status'));
        }
    }

    public function testAccountFinalStateCannotStrandLastMonetizationManager(): void
    {
        [$account, $old] = $this->fixture();
        $identity = DB::table('monetization_principals')->where('id', (string) $old)->value('identity_id');
        $repository = $this->app()->make(AccountPrincipalRepositoryInterface::class);
        $principals = $repository->findByAccountId($account);
        $overrides = [];
        foreach ($principals as $principal) {
            if ((string) $principal->identityIdentifier() === $identity) {
                $principal->changeStatus(AccountPrincipalStatus::SUSPENDED);
                $overrides[(string) $principal->principalIdentifier()] = $principal;
            }
        }
        $this->expectException(CannotRemoveLastPrincipalGroupManagerException::class);
        $this->app()->make(ChildPrincipalManagerContinuityInterface::class)->assertPreserved($account, $overrides);
    }

    public function testUnenrolledAccountDoesNotRequireMonetizationManager(): void
    {
        [$account] = $this->fixture();
        DB::table('monetization_principal_group_memberships')->delete();
        DB::table('monetization_principal_group_role_attachments')->delete();
        DB::table('monetization_principal_groups')->delete();
        DB::table('monetization_principals')->delete();
        $this->app()->make(ChildPrincipalManagerContinuityInterface::class)->assertPreserved($account, []);
        self::assertSame(0, DB::table('monetization_principals')->count());
    }

    private function deny(AccountIdentifier $account, PrincipalIdentifier $principal): void
    {
        $policy = StrTestHelper::generateUuid();
        $role = StrTestHelper::generateUuid();
        $group = StrTestHelper::generateUuid();
        DB::table('monetization_policies')->insert(['id' => $policy, 'name' => 'Deny management', 'account_id' => (string) $account, 'statements' => json_encode([['effect' => 'deny', 'actions' => ['principal_group_manage'], 'resource_types' => ['principal_group'], 'condition' => null]], JSON_THROW_ON_ERROR)]);
        DB::table('monetization_roles')->insert(['id' => $role, 'name' => 'Deny', 'account_id' => (string) $account]);
        DB::table('monetization_role_policy_attachments')->insert(['role_id' => $role, 'policy_id' => $policy]);
        DB::table('monetization_principal_groups')->insert(['id' => $group, 'name' => 'Deny', 'account_id' => (string) $account, 'is_default' => false]);
        DB::table('monetization_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
        DB::table('monetization_principal_group_memberships')->insert(['principal_group_id' => $group, 'principal_id' => (string) $principal]);
    }

    private function member(AccountIdentifier $account): PrincipalIdentifier
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        $accountPrincipal = StrTestHelper::generateUuid();
        $accountGroup = StrTestHelper::generateUuid();
        DB::table('account_principals')->insert(['id' => $accountPrincipal, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        DB::table('account_principal_groups')->insert(['id' => $accountGroup, 'account_id' => (string) $account, 'name' => 'Members', 'is_default' => false]);
        DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_group_id' => $accountGroup, 'principal_id' => $accountPrincipal]);
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        DB::table('monetization_principals')->insert(['id' => (string) $principal, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);

        return $principal;
    }
}
