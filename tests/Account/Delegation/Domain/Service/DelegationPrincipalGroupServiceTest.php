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
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Account\Delegation\Domain\Service\DelegationPrincipalGroupService;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationPrincipalGroupServiceTest extends TestCase
{
    public function testCreatesGroupInDelegateAccountAndAttachesDelegationAccountSwitcherRole(): void
    {
        $delegate = new AccountIdentifier(StrTestHelper::generateUuid());
        $delegator = new AccountIdentifier(StrTestHelper::generateUuid());
        $delegatorAccount = new Account(
            $delegator,
            new Email('delegator@example.com'),
            AccountType::CORPORATION,
            new AccountName('相手アカウント'),
            AccountStatus::ACTIVE,
            AccountCategory::AGENCY,
            DeletionReadinessChecklist::ready(),
            new AccountDocuments([]),
        );
        $delegation = new AccountDelegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $delegate,
            $delegator,
            $delegate,
            DelegationStatus::APPROVED,
            DelegationDirection::FROM_AGENCY,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
        );
        $role = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), Role::DELEGATION_ACCOUNT_SWITCHER, [], true);
        $group = Mockery::mock(PrincipalGroup::class);
        $group->shouldReceive('addRole')->with($role->roleIdentifier())->once();
        /** @var PrincipalGroupFactoryInterface&Mockery\MockInterface $factory */
        $factory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $factory->shouldReceive('create')->with($delegate, 'Delegation - 相手アカウント', false)->once()->andReturn($group);
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $groups */
        $groups = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $groups->shouldReceive('save')->with($group)->once();
        /** @var RoleRepositoryInterface&Mockery\MockInterface $roles */
        $roles = Mockery::mock(RoleRepositoryInterface::class);
        $roles->shouldReceive('findByName')->with(Role::DELEGATION_ACCOUNT_SWITCHER)->once()->andReturn($role);
        /** @var AccountRepositoryInterface&Mockery\MockInterface $accounts */
        $accounts = Mockery::mock(AccountRepositoryInterface::class);
        $accounts->shouldReceive('findById')->with($delegator)->once()->andReturn($delegatorAccount);
        (new DelegationPrincipalGroupService($factory, $groups, $roles, $accounts))->createFor($delegation);
        $this->addToAssertionCount(1);
    }
}
