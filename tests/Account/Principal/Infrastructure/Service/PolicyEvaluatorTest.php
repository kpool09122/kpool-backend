<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Service;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
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
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroup = $this->createPrincipalGroup($accountIdentifier, $principal->principalIdentifier(), $roleIdentifier);
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
        $roleRepository->shouldReceive('findByIds')
            ->once()
            ->with([$roleIdentifier])
            ->andReturn([
                (string) $roleIdentifier => new Role($roleIdentifier, Role::OWNER, [$policy->policyIdentifier()], true),
            ]);

        $evaluator = $this->makePolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);

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
        $roleRepository->shouldNotReceive('findByIds');

        $evaluator = $this->makePolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);

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
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroup = $this->createPrincipalGroup($accountIdentifier, $principal->principalIdentifier(), $roleIdentifier);
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
        $roleRepository->shouldReceive('findByIds')
            ->once()
            ->with([$roleIdentifier])
            ->andReturn([
                (string) $roleIdentifier => new Role(
                    $roleIdentifier,
                    Role::ADMIN,
                    [$allowPolicy->policyIdentifier(), $denyPolicy->policyIdentifier()],
                    true,
                ),
            ]);

        $evaluator = $this->makePolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);

        $this->assertFalse($evaluator->evaluate(
            $principal,
            Action::INVITE_MEMBER,
            Resource::account($accountIdentifier),
        ));
    }

    public function testEvaluateAllowsWhenConditionMatchesResourceAccountType(): void
    {
        $this->assertSame(
            true,
            $this->evaluateInvitationWithAccountTypeCondition(AccountType::CORPORATION)
        );
    }

    public function testEvaluateDeniesWhenConditionDoesNotMatchResourceAccountType(): void
    {
        $this->assertSame(
            false,
            $this->evaluateInvitationWithAccountTypeCondition(AccountType::INDIVIDUAL)
        );
    }

    public function testEvaluateDeniesWhenConditionRequiresAccountTypeButResourceDoesNotHaveIt(): void
    {
        $this->assertSame(
            false,
            $this->evaluateInvitationWithAccountTypeCondition(null)
        );
    }

    public function testEvaluateAllowsWhenConditionMatchesResourceAccountCategory(): void
    {
        $this->assertTrue($this->evaluateInvitationWithAccountCategoryCondition(AccountCategory::TALENT));
    }

    public function testEvaluateDeniesWhenConditionDoesNotMatchResourceAccountCategory(): void
    {
        $this->assertFalse($this->evaluateInvitationWithAccountCategoryCondition(AccountCategory::GENERAL));
    }

    public function testEvaluateAllowsWhenAffiliationRequestPairIsAllowed(): void
    {
        $this->assertTrue($this->evaluateAffiliationRequestReceive(AccountCategory::TALENT, AccountCategory::AGENCY));
        $this->assertTrue($this->evaluateAffiliationRequestReceive(AccountCategory::AGENCY, AccountCategory::TALENT));
    }

    public function testEvaluateDeniesWhenAffiliationRequestPairIsNotAllowed(): void
    {
        $this->assertFalse($this->evaluateAffiliationRequestReceive(AccountCategory::TALENT, AccountCategory::TALENT));
        $this->assertFalse($this->evaluateAffiliationRequestReceive(AccountCategory::AGENCY, AccountCategory::AGENCY));
        $this->assertFalse($this->evaluateAffiliationRequestReceive(AccountCategory::GENERAL, AccountCategory::AGENCY));
    }

    /**
     * @param Action[] $actions
     */
    private function createPolicy(string $name, Effect $effect, array $actions, ?Condition $condition = null): Policy
    {
        return new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            $name,
            [new Statement($effect, $actions, [ResourceType::ACCOUNT], $condition)],
            true,
            new DateTimeImmutable(),
        );
    }

    private function evaluateInvitationWithAccountTypeCondition(?AccountType $accountType): bool
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = $this->createPrincipal($accountIdentifier);
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroup = $this->createPrincipalGroup($accountIdentifier, $principal->principalIdentifier(), $roleIdentifier);
        $policy = $this->createPolicy(
            'CORPORATE_INVITATION',
            Effect::ALLOW,
            [Action::INVITE_MEMBER],
            new Condition([
                new ConditionClause(
                    ConditionKey::RESOURCE_ACCOUNT_TYPE,
                    ConditionOperator::EQUALS,
                    AccountType::CORPORATION->value,
                ),
            ]),
        );

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
        $roleRepository->shouldReceive('findByIds')
            ->once()
            ->with([$roleIdentifier])
            ->andReturn([
                (string) $roleIdentifier => new Role($roleIdentifier, Role::OWNER, [$policy->policyIdentifier()], true),
            ]);

        return $this->makePolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository)->evaluate(
            $principal,
            Action::INVITE_MEMBER,
            Resource::account($accountIdentifier, $accountType),
        );
    }

    private function evaluateInvitationWithAccountCategoryCondition(AccountCategory $accountCategory): bool
    {
        return $this->evaluateWithCondition(
            Action::INVITE_MEMBER,
            new Condition([
                new ConditionClause(
                    ConditionKey::RESOURCE_ACCOUNT_CATEGORY,
                    ConditionOperator::IN,
                    [
                        AccountCategory::TALENT->value,
                        AccountCategory::AGENCY->value,
                    ],
                ),
            ]),
            Resource::account(new AccountIdentifier(StrTestHelper::generateUuid()), null, $accountCategory),
        );
    }

    private function evaluateAffiliationRequestReceive(
        AccountCategory $requestingCategory,
        AccountCategory $targetCategory,
    ): bool {
        return $this->evaluateWithCondition(
            Action::AFFILIATION_REQUEST_RECEIVE,
            new Condition([
                new ConditionClause(
                    ConditionKey::AFFILIATION_REQUEST_PAIR_ALLOWED,
                    ConditionOperator::EQUALS,
                    true,
                ),
            ]),
            Resource::account(
                new AccountIdentifier(StrTestHelper::generateUuid()),
                null,
                $targetCategory,
                $requestingCategory,
            ),
        );
    }

    private function evaluateWithCondition(Action $action, Condition $condition, Resource $resource): bool
    {
        $accountIdentifier = $resource->accountIdentifier();
        $principal = $this->createPrincipal($accountIdentifier);
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroup = $this->createPrincipalGroup($accountIdentifier, $principal->principalIdentifier(), $roleIdentifier);
        $policy = $this->createPolicy('CONDITIONAL_POLICY', Effect::ALLOW, [$action], $condition);

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
        $roleRepository->shouldReceive('findByIds')
            ->once()
            ->with([$roleIdentifier])
            ->andReturn([
                (string) $roleIdentifier => new Role($roleIdentifier, Role::OWNER, [$policy->policyIdentifier()], true),
            ]);

        return $this->makePolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository)->evaluate(
            $principal,
            $action,
            $resource,
        );
    }

    private function makePolicyEvaluator(
        PrincipalGroupRepositoryInterface $principalGroupRepository,
        RoleRepositoryInterface $roleRepository,
        PolicyRepositoryInterface $policyRepository,
    ): PolicyEvaluatorInterface {
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(PolicyRepositoryInterface::class, $policyRepository);
        $this->app->forgetInstance(PolicyEvaluatorInterface::class);

        return $this->app->make(PolicyEvaluatorInterface::class);
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
        RoleIdentifier $roleIdentifier,
    ): PrincipalGroup {
        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Test Group',
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($principalIdentifier);
        $principalGroup->addRole($roleIdentifier);

        return $principalGroup;
    }
}
