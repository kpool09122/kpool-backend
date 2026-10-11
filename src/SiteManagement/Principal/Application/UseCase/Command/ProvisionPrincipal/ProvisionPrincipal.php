<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use RuntimeException;
use Source\Account\Principal\Domain\Entity\Role as AccountRole;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as AccountPrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\SiteManagement\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class ProvisionPrincipal implements ProvisionPrincipalInterface
{
    private const string DEFAULT_PRINCIPAL_GROUP_NAME = 'General';
    private const string GENERAL_ROLE_NAME = 'General';
    private const string ADMINISTRATOR_GROUP_NAME = 'SiteManagement Administrator';
    private const string ADMINISTRATOR_ROLE_NAME = 'Administrator';

    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private RoleRepositoryInterface $roleRepository,
        private AccountPrincipalRepositoryInterface $accountPrincipalRepository,
        private AccountPrincipalGroupRepositoryInterface $accountPrincipalGroupRepository,
        private AccountRoleRepositoryInterface $accountRoleRepository,
    ) {
    }

    public function process(ProvisionPrincipalInputPort $inputPort, ProvisionPrincipalOutputPort $output): void
    {
        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier($inputPort->identityIdentifier(), $inputPort->accountIdentifier());
        if ($principal === null) {
            $principal = $this->principalFactory->create($inputPort->identityIdentifier(), $inputPort->accountIdentifier());
            $group = $this->isAccountOwner($inputPort)
                ? $this->administratorGroup($inputPort)
                : $this->defaultGroup($inputPort);
            $this->principalRepository->save($principal);
            $group->addMember($principal->principalIdentifier());
            $this->principalGroupRepository->save($group);
        }
        $output->setPrincipal($principal);
    }

    private function isAccountOwner(ProvisionPrincipalInputPort $input): bool
    {
        $accountPrincipal = $this->accountPrincipalRepository->findByIdentityIdentifierAndAccountIdentifier($input->identityIdentifier(), $input->accountIdentifier());
        if ($accountPrincipal === null) {
            return false;
        }
        $ownerRole = $this->accountRoleRepository->findSystemByName(AccountRole::OWNER)
            ?? throw new RuntimeException('Owner account role is not found.');
        $ownerGroup = $this->accountPrincipalGroupRepository->findByAccountIdAndRole($input->accountIdentifier(), $ownerRole->roleIdentifier());

        return $ownerGroup?->hasMember($accountPrincipal->principalIdentifier()) ?? false;
    }

    private function defaultGroup(ProvisionPrincipalInputPort $input): PrincipalGroup
    {
        $group = $this->principalGroupRepository->findDefaultByAccountIdentifier($input->accountIdentifier());
        if ($group !== null) {
            return $group;
        }
        $role = $this->roleRepository->findSystemByName(self::GENERAL_ROLE_NAME)
            ?? throw new SystemRoleNotFoundException(self::GENERAL_ROLE_NAME);

        return $this->principalGroupFactory->create(self::DEFAULT_PRINCIPAL_GROUP_NAME, [$role->roleIdentifier()], $input->accountIdentifier(), true);
    }

    private function administratorGroup(ProvisionPrincipalInputPort $input): PrincipalGroup
    {
        $group = $this->principalGroupRepository->findByAccountIdAndName($input->accountIdentifier(), self::ADMINISTRATOR_GROUP_NAME)
            ?? $this->principalGroupFactory->create(self::ADMINISTRATOR_GROUP_NAME, [], $input->accountIdentifier(), false);
        foreach ([self::ADMINISTRATOR_ROLE_NAME, self::GENERAL_ROLE_NAME] as $roleName) {
            $role = $this->roleRepository->findSystemByName($roleName)
                ?? throw new SystemRoleNotFoundException($roleName);
            $group->addRole($role);
        }

        return $group;
    }
}
