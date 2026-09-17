<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Service;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Exception\DelegationPrincipalGroupCreationException;
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

    public function createFor(Delegation $delegation): void
    {
        $switcherRole = $this->roleRepository->findSystemByName(Role::DELEGATION_ACCOUNT_SWITCHER);
        if ($switcherRole === null) {
            throw DelegationPrincipalGroupCreationException::switcherRoleNotFound();
        }
        $delegatorAccount = $this->accountRepository->findById($delegation->delegatorAccountIdentifier());
        if ($delegatorAccount === null) {
            throw DelegationPrincipalGroupCreationException::delegatorAccountNotFound();
        }

        $principalGroup = $this->principalGroupFactory->create(
            $delegation->delegateAccountIdentifier(),
            sprintf('Delegation - %s', $delegatorAccount->name()),
            false,
        );
        $principalGroup->addRole($switcherRole);
        $this->principalGroupRepository->save($principalGroup);
    }
}
