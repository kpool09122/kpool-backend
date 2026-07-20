<?php

declare(strict_types=1);

namespace Tests\Account\Policy\Infrastructure\Service;

use DateTimeImmutable;
use Mockery;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Policy\Domain\Entity\AccountPolicy;
use Source\Account\Policy\Domain\Repository\AccountPolicyRepositoryInterface;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountPolicyIdentifier;
use Source\Account\Policy\Domain\ValueObject\AccountResource;
use Source\Account\Policy\Domain\ValueObject\AccountResourceType;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Source\Account\Policy\Domain\ValueObject\Statement;
use Source\Account\Policy\Infrastructure\Service\PolicyEvaluator;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PolicyEvaluatorTest extends TestCase
{
    public function testEvaluateAllowsWhenRolePolicyHasAllowStatement(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityGroup = $this->createIdentityGroup($accountIdentifier, $identityIdentifier, AccountRole::OWNER);
        $policy = $this->createPolicy('ACCOUNT_OWNER_BASIC', Effect::ALLOW, [AccountAction::INVITATION_CREATE]);

        /** @var IdentityGroupRepositoryInterface&\Mockery\MockInterface $identityGroupRepository */
        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($accountIdentifier, $identityIdentifier)
            ->andReturn([$identityGroup]);

        /** @var AccountPolicyRepositoryInterface&\Mockery\MockInterface $accountPolicyRepository */
        $accountPolicyRepository = Mockery::mock(AccountPolicyRepositoryInterface::class);
        $accountPolicyRepository->shouldReceive('findByRoles')
            ->once()
            ->with([AccountRole::OWNER])
            ->andReturn([$policy]);

        $evaluator = new PolicyEvaluator($identityGroupRepository, $accountPolicyRepository);

        $this->assertTrue($evaluator->evaluate(
            $identityIdentifier,
            AccountAction::INVITATION_CREATE,
            AccountResource::account($accountIdentifier),
        ));
    }

    public function testEvaluateDeniesWhenNoIdentityGroupExists(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        /** @var IdentityGroupRepositoryInterface&\Mockery\MockInterface $identityGroupRepository */
        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($accountIdentifier, $identityIdentifier)
            ->andReturn([]);

        /** @var AccountPolicyRepositoryInterface&\Mockery\MockInterface $accountPolicyRepository */
        $accountPolicyRepository = Mockery::mock(AccountPolicyRepositoryInterface::class);
        $accountPolicyRepository->shouldNotReceive('findByRoles');

        $evaluator = new PolicyEvaluator($identityGroupRepository, $accountPolicyRepository);

        $this->assertFalse($evaluator->evaluate(
            $identityIdentifier,
            AccountAction::INVITATION_CREATE,
            AccountResource::account($accountIdentifier),
        ));
    }

    public function testEvaluatePrioritizesExplicitDeny(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityGroup = $this->createIdentityGroup($accountIdentifier, $identityIdentifier, AccountRole::ADMIN);
        $allowPolicy = $this->createPolicy('ALLOW_INVITATION', Effect::ALLOW, [AccountAction::INVITATION_CREATE]);
        $denyPolicy = $this->createPolicy('DENY_INVITATION', Effect::DENY, [AccountAction::INVITATION_CREATE]);

        /** @var IdentityGroupRepositoryInterface&\Mockery\MockInterface $identityGroupRepository */
        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($accountIdentifier, $identityIdentifier)
            ->andReturn([$identityGroup]);

        /** @var AccountPolicyRepositoryInterface&\Mockery\MockInterface $accountPolicyRepository */
        $accountPolicyRepository = Mockery::mock(AccountPolicyRepositoryInterface::class);
        $accountPolicyRepository->shouldReceive('findByRoles')
            ->once()
            ->with([AccountRole::ADMIN])
            ->andReturn([$allowPolicy, $denyPolicy]);

        $evaluator = new PolicyEvaluator($identityGroupRepository, $accountPolicyRepository);

        $this->assertFalse($evaluator->evaluate(
            $identityIdentifier,
            AccountAction::INVITATION_CREATE,
            AccountResource::account($accountIdentifier),
        ));
    }

    /**
     * @param AccountAction[] $actions
     */
    private function createPolicy(string $name, Effect $effect, array $actions): AccountPolicy
    {
        return new AccountPolicy(
            new AccountPolicyIdentifier(StrTestHelper::generateUuid()),
            $name,
            [new Statement($effect, $actions, [AccountResourceType::ACCOUNT])],
            true,
            new DateTimeImmutable(),
        );
    }

    private function createIdentityGroup(
        AccountIdentifier $accountIdentifier,
        IdentityIdentifier $identityIdentifier,
        AccountRole $role,
    ): IdentityGroup {
        $identityGroup = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Test Group',
            $role,
            true,
            new DateTimeImmutable(),
        );
        $identityGroup->addMember($identityIdentifier);

        return $identityGroup;
    }
}
