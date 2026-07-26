<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Account\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Infrastructure\Query\Authorization\PrincipalGroupManageAuthorization;
use Source\Account\Principal\Infrastructure\Query\ListPrincipalGroups;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListPrincipalGroupsTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));
        $this->assertInstanceOf(ListPrincipalGroups::class, $this->app->make(ListPrincipalGroupsInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsPrincipalGroupsForAccount(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $principal = $this->domainPrincipal($accountIdentifier);
        $groupId = $this->createPrincipalGroup((string) $accountIdentifier, 'Admins', false);
        $roleId = StrTestHelper::generateUuid();
        $memberId = StrTestHelper::generateUuid();
        $identityId = StrTestHelper::generateUuid();
        CreateIdentity::create(new IdentityIdentifier($identityId), [
            'identityName' => 'alice',
            'email' => 'alice@example.com',
        ]);
        DB::table('account_principals')->insert([
            'id' => $memberId,
            'identity_id' => $identityId,
            'account_id' => (string) $accountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $secondMemberId = StrTestHelper::generateUuid();
        $secondIdentityId = StrTestHelper::generateUuid();
        CreateIdentity::create(new IdentityIdentifier($secondIdentityId), [
            'identityName' => 'bob',
            'email' => 'bob@example.com',
        ]);
        DB::table('account_principals')->insert([
            'id' => $secondMemberId,
            'identity_id' => $secondIdentityId,
            'account_id' => (string) $accountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_roles')->insert([
            'id' => $roleId,
            'name' => 'Admin Role',
            'is_system_role' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_principal_group_role_attachments')->insert([
            'principal_group_id' => $groupId,
            'role_id' => $roleId,
        ]);
        DB::table('account_principal_group_memberships')->insert([
            [
                'id' => StrTestHelper::generateUuid(),
                'principal_group_id' => $groupId,
                'principal_id' => $memberId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => StrTestHelper::generateUuid(),
                'principal_group_id' => $groupId,
                'principal_id' => $secondMemberId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::PRINCIPAL_GROUP_MANAGE, Mockery::type(Resource::class))
            ->andReturnTrue();

        $groups = (new ListPrincipalGroups(new PrincipalGroupManageAuthorization($policyEvaluator)))
            ->process(new ListPrincipalGroupsInput($accountIdentifier, $principal));

        $this->assertCount(1, $groups);
        $this->assertInstanceOf(PrincipalGroupReadModel::class, $groups[0]);
        $this->assertSame([
            'principalGroupIdentifier' => $groupId,
            'accountIdentifier' => (string) $accountIdentifier,
            'name' => 'Admins',
            'roleIdentifiers' => [$roleId],
            'isDefault' => false,
            'members' => [
                [
                    'principalIdentifier' => $memberId,
                    'identityIdentifier' => $identityId,
                    'identityName' => 'alice',
                    'email' => 'alice@example.com',
                ],
                [
                    'principalIdentifier' => $secondMemberId,
                    'identityIdentifier' => $secondIdentityId,
                    'identityName' => 'bob',
                    'email' => 'bob@example.com',
                ],
            ],
        ], $groups[0]->toArray());
    }

    #[Group('useDb')]
    public function testProcessThrowsForbiddenForDifferentAccountPrincipal(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->domainPrincipal(new AccountIdentifier(StrTestHelper::generateUuid()));
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldNotReceive('evaluate');

        $this->expectException(AccountUpdateForbiddenException::class);
        (new ListPrincipalGroups(new PrincipalGroupManageAuthorization($policyEvaluator)))
            ->process(new ListPrincipalGroupsInput($accountIdentifier, $principal));
    }

    private function domainPrincipal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountIdentifier);
    }

    private function createPrincipalGroup(string $accountId, string $name, bool $isDefault): string
    {
        $id = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert([
            'id' => $id,
            'account_id' => $accountId,
            'name' => $name,
            'is_default' => $isDefault,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
