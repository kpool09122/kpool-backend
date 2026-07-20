<?php

declare(strict_types=1);

namespace Tests\Account\Policy\Infrastructure\Repository;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Policy\Domain\Entity\AccountPolicy;
use Source\Account\Policy\Domain\Repository\AccountPolicyRepositoryInterface;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountPolicyIdentifier;
use Source\Account\Policy\Domain\ValueObject\AccountResourceType;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Source\Account\Policy\Domain\ValueObject\Statement;
use Source\Account\Policy\Infrastructure\Repository\AccountPolicyRepository;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountPolicyRepositoryTest extends TestCase
{
    public function test__construct(): void
    {
        $repository = $this->app->make(AccountPolicyRepositoryInterface::class);

        $this->assertInstanceOf(AccountPolicyRepository::class, $repository);
    }

    #[Group('useDb')]
    public function testSaveAttachToRoleAndFindByRoles(): void
    {
        $policy = new AccountPolicy(
            new AccountPolicyIdentifier(StrTestHelper::generateUuid()),
            'ACCOUNT_INVITATION_CREATE_TEST',
            [new Statement(
                Effect::ALLOW,
                [AccountAction::INVITATION_CREATE],
                [AccountResourceType::ACCOUNT],
            )],
            true,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(AccountPolicyRepositoryInterface::class);
        $repository->save($policy);
        $repository->attachToRole(AccountRole::ADMIN, $policy->accountPolicyIdentifier());

        $this->assertDatabaseHas('account_policies', [
            'id' => (string) $policy->accountPolicyIdentifier(),
            'name' => 'ACCOUNT_INVITATION_CREATE_TEST',
            'is_system_policy' => true,
        ]);
        $this->assertDatabaseHas('account_role_policy_attachments', [
            'role' => 'admin',
            'policy_id' => (string) $policy->accountPolicyIdentifier(),
        ]);

        $foundPolicies = $repository->findByRoles([AccountRole::ADMIN]);

        $this->assertCount(1, $foundPolicies);
        $this->assertSame('ACCOUNT_INVITATION_CREATE_TEST', $foundPolicies[0]->name());
        $this->assertSame(Effect::ALLOW, $foundPolicies[0]->statements()[0]->effect());
        $this->assertSame(AccountAction::INVITATION_CREATE, $foundPolicies[0]->statements()[0]->actions()[0]);
        $this->assertSame(AccountResourceType::ACCOUNT, $foundPolicies[0]->statements()[0]->resourceTypes()[0]);
    }
}
