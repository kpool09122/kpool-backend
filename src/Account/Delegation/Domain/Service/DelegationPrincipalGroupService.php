<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Service;

use RuntimeException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class DelegationPrincipalGroupService implements DelegationPrincipalGroupServiceInterface
{
    public function __construct(
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function createFor(AccountDelegation $delegation): void
    {
        $switcherRole = $this->roleRepository->findByName(Role::DELEGATION_ACCOUNT_SWITCHER);
        if ($switcherRole === null) {
            throw new RuntimeException('Delegation account switcher role is not found.');
        }
        $delegatorAccount = $this->accountRepository->findById($delegation->delegatorAccountIdentifier());
        if ($delegatorAccount === null) {
            throw new RuntimeException('Delegator account is not found.');
        }

        $principalGroup = $this->principalGroupFactory->create(
            $delegation->delegateAccountIdentifier(),
            sprintf('Delegation - %s', $delegatorAccount->name()),
            false,
        );
        $principalGroup->addRole($switcherRole->roleIdentifier());
        $this->principalGroupRepository->save($principalGroup);
    }
}
