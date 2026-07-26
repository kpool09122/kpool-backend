<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Service;

use DateTimeImmutable;
use Mockery;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PolicyEvaluatorTest extends TestCase
{
    public function testEvaluateAllowsWhenRolePolicyHasAllowStatement(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);
        $principalGroup = $this->createPrincipalGroup($accountIdentifier, $principal->principalIdentifier(), AccountRole::OWNER);
        $policy = $this->createPolicy('ACCOUNT_OWNER_BASIC', Effect::ALLOW, [Action::INVITE_MEMBER]);

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndPrincipal')
            ->once()
            ->with($accountIdentifier, $principal->principalIdentifier())
            ->andReturn([$principalGroup]);

        /** @var PolicyRepositoryInterface&\Mockery\MockInterface $policyRepository */
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findByIds')
            ->once()
            ->with([$policy->policyIdentifier()])
            ->andReturn([(string) $policy->policyIdentifier() => $policy]);

        /** @var RoleRepositoryInterface&\Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByRoles')
            ->once()
            ->with([AccountRole::OWNER])
            ->andReturn([AccountRole::OWNER->value => new Role(AccountRole::OWNER, [$policy->policyIdentifier()])]);

        $evaluator = new PolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);

        $this->assertTrue($evaluator->evaluate(
            $principal,
            Action::INVITE_MEMBER,
            Resource::account($accountIdentifier),
        ));
    }

    public function testEvaluateDeniesWhenNoPrincipalGroupExists(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndPrincipal')
            ->once()
            ->with($accountIdentifier, $principal->principalIdentifier())
            ->andReturn([]);

        /** @var PolicyRepositoryInterface&\Mockery\MockInterface $policyRepository */
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldNotReceive('findByIds');

        /** @var RoleRepositoryInterface&\Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('findByRoles');

        $evaluator = new PolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);

        $this->assertFalse($evaluator->evaluate(
            $principal,
            Action::INVITE_MEMBER,
            Resource::account($accountIdentifier),
        ));
    }

    public function testEvaluatePrioritizesExplicitDeny(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);
        $principalGroup = $this->createPrincipalGroup($accountIdentifier, $principal->principalIdentifier(), AccountRole::ADMIN);
        $allowPolicy = $this->createPolicy('ALLOW_INVITATION', Effect::ALLOW, [Action::INVITE_MEMBER]);
        $denyPolicy = $this->createPolicy('DENY_INVITATION', Effect::DENY, [Action::INVITE_MEMBER]);

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndPrincipal')
            ->once()
            ->with($accountIdentifier, $principal->principalIdentifier())
            ->andReturn([$principalGroup]);

        /** @var PolicyRepositoryInterface&\Mockery\MockInterface $policyRepository */
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findByIds')
            ->once()
            ->with([$allowPolicy->policyIdentifier(), $denyPolicy->policyIdentifier()])
            ->andReturn([
                (string) $allowPolicy->policyIdentifier() => $allowPolicy,
                (string) $denyPolicy->policyIdentifier() => $denyPolicy,
            ]);

        /** @var RoleRepositoryInterface&\Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByRoles')
            ->once()
            ->with([AccountRole::ADMIN])
            ->andReturn([
                AccountRole::ADMIN->value => new Role(
                    AccountRole::ADMIN,
                    [$allowPolicy->policyIdentifier(), $denyPolicy->policyIdentifier()],
                ),
            ]);

        $evaluator = new PolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);

        $this->assertFalse($evaluator->evaluate(
            $principal,
            Action::INVITE_MEMBER,
            Resource::account($accountIdentifier),
        ));
    }

    /**
     * @param Action[] $actions
     */
    private function createPolicy(string $name, Effect $effect, array $actions): Policy
    {
        return new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            $name,
            [new Statement($effect, $actions, [ResourceType::ACCOUNT])],
            true,
            new DateTimeImmutable(),
        );
    }

    private function createPrincipal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
    }

    private function createPrincipalGroup(
        AccountIdentifier $accountIdentifier,
        PrincipalIdentifier $principalIdentifier,
        AccountRole $role,
    ): PrincipalGroup {
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Test Group',
            $role,
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($principalIdentifier);

        return $principalGroup;
    }
}
