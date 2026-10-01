<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\AdministratorRoleNotAttachedException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class RevokeWikiAdministrator implements RevokeWikiAdministratorInterface
{
    private const string ADMINISTRATOR_ROLE_NAME = 'ADMINISTRATOR';
    private const string ADMINISTRATOR_GROUP_NAME = 'Operations Wiki Administrators';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(RevokeWikiAdministratorInputPort $input, RevokeWikiAdministratorOutputPort $output): void
    {
        $account = $this->accountRepository->findByEmail($input->email());
        if ($account === null) {
            throw new AccountNotFoundException('指定されたメールアドレスのAccountが見つかりません。');
        }

        $administratorRole = $this->roleRepository->findSystemByName(self::ADMINISTRATOR_ROLE_NAME);
        if ($administratorRole === null) {
            throw new SystemRoleNotFoundException(self::ADMINISTRATOR_ROLE_NAME);
        }

        $principalGroup = $this->principalGroupRepository->findByAccountIdAndName(
            $account->accountIdentifier(),
            self::ADMINISTRATOR_GROUP_NAME,
        );
        if ($principalGroup === null) {
            throw new PrincipalGroupNotFoundException('Operations Wiki Administratorsグループが見つかりません。');
        }
        if (! $principalGroup->hasRole($administratorRole->roleIdentifier())) {
            throw new AdministratorRoleNotAttachedException();
        }

        $this->principalGroupRepository->delete($principalGroup);
    }
}
