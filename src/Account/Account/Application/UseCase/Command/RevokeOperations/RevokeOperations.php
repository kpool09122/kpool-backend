<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RevokeOperations;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class RevokeOperations implements RevokeOperationsInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(RevokeOperationsInputPort $input, RevokeOperationsOutputPort $output): void
    {
        $account = $this->accountRepository->findByEmail($input->email());
        if ($account === null) {
            throw new AccountNotFoundException('指定されたメールアドレスのAccountが見つかりません。');
        }

        $operationsRole = $this->roleRepository->findSystemByName(Role::OPERATIONS);
        if ($operationsRole === null) {
            throw new SystemRoleNotFoundException(Role::OPERATIONS);
        }

        $principalGroup = $this->principalGroupRepository->findByAccountIdAndRole(
            $account->accountIdentifier(),
            $operationsRole->roleIdentifier(),
        );
        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException('Operations権限を持つPrincipalGroupが見つかりません。');
        }

        $this->principalGroupRepository->delete($principalGroup);
    }
}
