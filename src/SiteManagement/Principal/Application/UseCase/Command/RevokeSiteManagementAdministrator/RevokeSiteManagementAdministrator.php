<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\RevokeSiteManagementAdministrator;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\SiteManagement\Principal\Application\Exception\AdministratorGroupIsDefaultException;
use Source\SiteManagement\Principal\Application\Exception\AdministratorRoleNotAttachedException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class RevokeSiteManagementAdministrator implements RevokeSiteManagementAdministratorInterface
{
    private const string ADMINISTRATOR_ROLE_NAME = 'administrator';
    private const string ADMINISTRATOR_GROUP_NAME = 'Operations SiteManagement Administrators';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(RevokeSiteManagementAdministratorInputPort $input, RevokeSiteManagementAdministratorOutputPort $output): void
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
            throw new PrincipalGroupNotFoundException('Operations SiteManagement Administratorsグループが見つかりません。');
        }
        if ($principalGroup->isDefault()) {
            throw new AdministratorGroupIsDefaultException();
        }

        if (! $principalGroup->hasRole($administratorRole->roleIdentifier())) {
            throw new AdministratorRoleNotAttachedException();
        }

        $this->principalGroupRepository->delete($principalGroup);
    }
}
