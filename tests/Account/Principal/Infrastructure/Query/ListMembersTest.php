<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\UseCase\Query\ListMembers\ListMembersInput;
use Source\Account\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Account\Principal\Application\UseCase\Query\MemberReadModel;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Infrastructure\Query\ListMembers;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListMembersTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));
        $this->assertInstanceOf(ListMembers::class, $this->app->make(ListMembersInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsAccountMembersWithIdentityAndPrincipalGroups(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        $principal = $this->domainPrincipal($accountIdentifier);
        $memberPrincipalId = StrTestHelper::generateUuid();
        $otherAccountId = StrTestHelper::generateUuid();
        CreateAccount::create($otherAccountId);

        $identityId = StrTestHelper::generateUuid();
        CreateIdentity::create(new IdentityIdentifier($identityId), [
            'identity_name' => 'alice',
            'email' => 'alice@example.com',
        ]);
        $this->createPrincipal($memberPrincipalId, $identityId, (string) $accountIdentifier);
        $this->createPrincipal(StrTestHelper::generateUuid(), $identityId, $otherAccountId);

        $principalGroupId = $this->createPrincipalGroup((string) $accountIdentifier, 'Owners', true);
        DB::table('account_principal_group_memberships')->insert([
            'id' => StrTestHelper::generateUuid(),
            'principal_group_id' => $principalGroupId,
            'principal_id' => $memberPrincipalId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($principal, Action::PRINCIPAL_GROUP_MANAGE, Mockery::type(Resource::class))
            ->andReturnTrue();

        $members = (new ListMembers(new \Source\Account\Principal\Infrastructure\Query\Authorization\PrincipalGroupManageAuthorization($policyEvaluator)))->process(
            new ListMembersInput($accountIdentifier, $principal),
        );

        $this->assertCount(1, $members);
        $this->assertInstanceOf(MemberReadModel::class, $members[0]);
        $this->assertSame($memberPrincipalId, $members[0]->principalIdentifier());
        $this->assertSame($identityId, $members[0]->identityIdentifier());
        $this->assertSame('alice', $members[0]->identityName());
        $this->assertSame('alice@example.com', $members[0]->email());
        $this->assertSame([
            [
                'principalGroupIdentifier' => $principalGroupId,
                'name' => 'Owners',
                'isDefault' => true,
            ],
        ], $members[0]->toArray()['principalGroups']);
    }

    #[Group('useDb')]
    public function testProcessThrowsForbiddenWhenPolicyDenies(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->domainPrincipal($accountIdentifier);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        $this->expectException(AccountUpdateForbiddenException::class);
        (new ListMembers(new \Source\Account\Principal\Infrastructure\Query\Authorization\PrincipalGroupManageAuthorization($policyEvaluator)))
            ->process(new ListMembersInput($accountIdentifier, $principal));
    }

    private function domainPrincipal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountIdentifier);
    }

    private function createPrincipal(string $principalId, string $identityId, string $accountId): void
    {
        DB::table('account_principals')->insert([
            'id' => $principalId,
            'identity_id' => $identityId,
            'account_id' => $accountId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
