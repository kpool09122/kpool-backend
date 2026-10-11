<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException as AccountPrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException as AccountPrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\Role as AccountRole;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException as AccountSystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as AccountPrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\OperationsPermissionRequiredException;
use Source\Wiki\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class GrantWikiOperator implements GrantWikiOperatorInterface
{
    private const string OPERATOR_ROLE_NAME = 'Operator';
    private const string OPERATOR_GROUP_NAME = 'Operations Wiki Operators';

    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private AccountRepositoryInterface $accountRepository,
        private AccountPrincipalRepositoryInterface $accountPrincipalRepository,
        private AccountPrincipalGroupRepositoryInterface $accountPrincipalGroupRepository,
        private AccountRoleRepositoryInterface $accountRoleRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(GrantWikiOperatorInputPort $input, GrantWikiOperatorOutputPort $output): void
    {
        $identity = $this->identityRepository->findByEmail($input->email());
        if ($identity === null) {
            throw new IdentityNotFoundException('Identityが見つかりません。');
        }

        $account = $this->accountRepository->findByEmail($input->email());
        if ($account === null) {
            throw new AccountNotFoundException('指定されたメールアドレスのAccountが見つかりません。');
        }

        $accountPrincipal = $this->accountPrincipalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $identity->identityIdentifier(),
            $account->accountIdentifier(),
        );
        if ($accountPrincipal === null) {
            throw new AccountPrincipalNotFoundException('Accountと同じメールアドレスのIdentityが、このAccountに所属していません。');
        }

        $operationsRole = $this->accountRoleRepository->findSystemByName(AccountRole::OPERATIONS);
        if ($operationsRole === null) {
            throw new AccountSystemRoleNotFoundException(AccountRole::OPERATIONS);
        }

        $operationsGroup = $this->accountPrincipalGroupRepository->findByAccountIdAndRole(
            $account->accountIdentifier(),
            $operationsRole->roleIdentifier(),
        );
        if ($operationsGroup === null) {
            throw new AccountPrincipalGroupNotFoundException('Operations権限を持つPrincipalGroupが見つかりません。');
        }
        if (! $operationsGroup->hasMember($accountPrincipal->principalIdentifier())) {
            throw new OperationsPermissionRequiredException();
        }

        $operatorRole = $this->roleRepository->findSystemByName(self::OPERATOR_ROLE_NAME);
        if ($operatorRole === null) {
            throw new SystemRoleNotFoundException(self::OPERATOR_ROLE_NAME);
        }

        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $identity->identityIdentifier(),
            $account->accountIdentifier(),
        );
        if ($principal === null) {
            throw new PrincipalNotFoundException('Accountと同じメールアドレスのIdentityに紐づくWiki Principalが見つかりません。');
        }

        $principalGroup = $this->principalGroupRepository->findByAccountIdAndName(
            $account->accountIdentifier(),
            self::OPERATOR_GROUP_NAME,
        );
        if ($principalGroup === null) {
            $principalGroup = $this->principalGroupFactory->create(
                $account->accountIdentifier(),
                self::OPERATOR_GROUP_NAME,
                false,
            );
        }

        $principalGroup->addRole($operatorRole);
        if (! $principalGroup->hasMember($principal->principalIdentifier())) {
            $principalGroup->addMember($principal->principalIdentifier());
        }
        $this->principalGroupRepository->save($principalGroup);
    }
}
