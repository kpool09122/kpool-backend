<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\GrantOperations;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\EmailNotVerifiedException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

readonly class GrantOperations implements GrantOperationsInterface
{
    private const string OPERATIONS_GROUP_NAME = 'Operations';

    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(GrantOperationsInputPort $input, GrantOperationsOutputPort $output): void
    {
        $identity = $this->identityRepository->findByEmail($input->email());
        if ($identity === null) {
            throw new IdentityNotFoundException('Identityが見つかりません。');
        }
        if ($identity->emailVerifiedAt() === null) {
            throw new EmailNotVerifiedException();
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
            $principalGroup = $this->principalGroupFactory->create(
                $account->accountIdentifier(),
                self::OPERATIONS_GROUP_NAME,
                false,
            );
        }
        $principalGroup->addRole($operationsRole);

        if (! $principalGroup->hasMember($principal->principalIdentifier())) {
            $principalGroup->addMember($principal->principalIdentifier());
        }

        $this->principalGroupRepository->save($principalGroup);
    }
}
