<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RevokeOperations;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\OperationsMembershipNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

readonly class RevokeOperations implements RevokeOperationsInterface
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(RevokeOperationsInputPort $input, RevokeOperationsOutputPort $output): void
    {
        $identity = $this->identityRepository->findByEmail($input->email());
        if ($identity === null) {
            throw new IdentityNotFoundException('Identityが見つかりません。');
        }

        $account = $this->accountRepository->findByEmail($input->email());
        if ($account === null) {
            throw new AccountNotFoundException('指定されたメールアドレスのAccountが見つかりません。');
        }

        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $identity->identityIdentifier(),
            $account->accountIdentifier(),
        );
        if ($principal === null) {
            throw new PrincipalNotFoundException('Accountと同じメールアドレスのIdentityが、このAccountに所属していません。');
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

        if (! $principalGroup->hasMember($principal->principalIdentifier())) {
            throw new OperationsMembershipNotFoundException();
        }

        $this->principalGroupRepository->delete($principalGroup);
    }
}
