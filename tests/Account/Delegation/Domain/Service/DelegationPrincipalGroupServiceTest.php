<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Domain\Service;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountDocuments;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Account\Domain\ValueObject\DeletionReadinessChecklist;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Service\DelegationPrincipalGroupService;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationPrincipalGroupServiceTest extends TestCase
{
    public function testCreatesScopedPolicyRoleAndGroupIdempotently(): void
    {
        $delegation = $this->delegation();
        $source = $delegation->delegateAccountIdentifier();
        $target = $delegation->delegatorAccountIdentifier();
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $source);
        $policy = new Policy(new PolicyIdentifier(StrTestHelper::generateUuid()), 'policy', [], $source, new DateTimeImmutable());
        $role = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'role', [], $source);
        $group = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $source,
            'Delegation - Target',
            false,
            new DateTimeImmutable(),
            delegationIdentifier: $delegation->delegationIdentifier(),
        );

        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $groups */
        $groups = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $groups->shouldReceive('findByDelegationId')->twice()->andReturn(null, $group);
        $groups->shouldReceive('save')->once()->with($group);
        /** @var PrincipalGroupFactoryInterface&Mockery\MockInterface $groupFactory */
        $groupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')->once()->with(
            $source,
            'Delegation - Target',
            false,
            $delegation->delegationIdentifier(),
        )->andReturn($group);
        /** @var PolicyFactoryInterface&Mockery\MockInterface $policyFactory */
        $policyFactory = Mockery::mock(PolicyFactoryInterface::class);
        $policyFactory->shouldReceive('create')->once()->withArgs(function (string $name, array $statements, AccountIdentifier $account) use ($delegation, $source, $target): bool {
            $clauses = $statements[0]->condition()?->clauses() ?? [];

            return str_contains($name, (string) $delegation->delegationIdentifier())
                && (string) $account === (string) $source
                && $statements[0]->effect() === Effect::ALLOW
                && $statements[0]->actions() === [Action::DELEGATION_ACCOUNT_SWITCH]
                && $statements[0]->resourceTypes() === [ResourceType::ACCOUNT]
                && $clauses[0]->key() === ConditionKey::RESOURCE_DELEGATION_ID
                && $clauses[0]->value() === (string) $delegation->delegationIdentifier()
                && $clauses[1]->key() === ConditionKey::RESOURCE_TARGET_ACCOUNT_ID
                && $clauses[1]->value() === (string) $target;
        })->andReturn($policy);
        /** @var PolicyRepositoryInterface&Mockery\MockInterface $policies */
        $policies = Mockery::mock(PolicyRepositoryInterface::class);
        $policies->shouldReceive('save')->once()->with($policy);
        /** @var RoleFactoryInterface&Mockery\MockInterface $roleFactory */
        $roleFactory = Mockery::mock(RoleFactoryInterface::class);
        $roleFactory->shouldReceive('create')->once()->with(
            'Delegation Role - ' . (string) $delegation->delegationIdentifier(),
            [],
            $source,
        )->andReturn($role);
        /** @var RoleRepositoryInterface&Mockery\MockInterface $roles */
        $roles = Mockery::mock(RoleRepositoryInterface::class);
        $roles->shouldReceive('save')->once()->with($role);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principals */
        $principals = Mockery::mock(PrincipalRepositoryInterface::class);
        $principals->shouldReceive('findByAccountId')->once()->with($source)->andReturn([$principal]);
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accounts */
        $accounts = Mockery::mock(AccountRepositoryInterface::class);
        $accounts->shouldReceive('findById')->once()->with($target)->andReturn($this->account($target));

        $service = new DelegationPrincipalGroupService($groupFactory, $groups, $policyFactory, $policies, $roleFactory, $roles, $principals, $accounts);
        $service->createFor($delegation);
        $service->createFor($delegation);

        $this->assertTrue($role->hasPolicy($policy->policyIdentifier()));
        $this->assertTrue($group->hasRole($role->roleIdentifier()));
        $this->assertTrue($group->hasMember($principal->principalIdentifier()));
    }

    private function delegation(): Delegation
    {
        $source = new AccountIdentifier(StrTestHelper::generateUuid());

        return new Delegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $source,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $source,
            DelegationStatus::APPROVED,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );
    }

    private function account(AccountIdentifier $identifier): Account
    {
        return new Account(
            $identifier,
            new Email('target@example.com'),
            AccountType::CORPORATION,
            new AccountName('Target'),
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments([]),
        );
    }
}
