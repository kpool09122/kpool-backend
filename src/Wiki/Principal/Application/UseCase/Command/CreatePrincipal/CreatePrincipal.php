<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use RuntimeException;
use Source\Account\Principal\Domain\Entity\Role as AccountRole;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as AccountPrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class CreatePrincipal implements CreatePrincipalInterface
{
    private const string DEFAULT_PRINCIPAL_GROUP_NAME = 'Default';
    private const string WIKI_ADMINISTRATOR_PRINCIPAL_GROUP_NAME = 'Wiki Administrator';
    private const string COLLABORATOR_ROLE = 'COLLABORATOR';
    private const string WIKI_ADMINISTRATOR_ROLE = 'WIKI_ADMINISTRATOR';

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

    /**
     * @param CreatePrincipalInputPort $input
     * @param CreatePrincipalOutputPort $output
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input, CreatePrincipalOutputPort $output): void
    {
        $existingPrincipal = $this->principalRepository->findByIdentityIdentifier(
            $input->identityIdentifier()
        );

        if ($existingPrincipal !== null) {
            throw new PrincipalAlreadyExistsException();
        }

        $principal = $this->principalFactory->create(
            $input->identityIdentifier(),
        );

        $this->principalRepository->save($principal);

        $initialPrincipalGroup = $this->isAccountOwner($input)
            ? $this->findOrCreateWikiAdministratorPrincipalGroup($input)
            : $this->findOrCreateDefaultPrincipalGroup($input);

        $initialPrincipalGroup->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($initialPrincipalGroup);

        $output->setPrincipal($principal);
    }

    private function findOrCreateDefaultPrincipalGroup(CreatePrincipalInputPort $input): PrincipalGroup
    {
        $defaultPrincipalGroup = $this->principalGroupRepository->findDefaultByAccountId($input->accountIdentifier());
        if ($defaultPrincipalGroup !== null) {
            return $defaultPrincipalGroup;
        }

        $defaultPrincipalGroup = $this->principalGroupFactory->create(
            $input->accountIdentifier(),
            self::DEFAULT_PRINCIPAL_GROUP_NAME,
            true,
        );

        $role = $this->roleRepository->findByName(self::COLLABORATOR_ROLE);
        if ($role !== null) {
            $defaultPrincipalGroup->addRole($role->roleIdentifier());
        }

        $this->principalGroupRepository->save($defaultPrincipalGroup);

        return $defaultPrincipalGroup;
    }

    private function findOrCreateWikiAdministratorPrincipalGroup(CreatePrincipalInputPort $input): PrincipalGroup
    {
        $wikiAdministratorPrincipalGroup = $this->principalGroupRepository->findByAccountIdAndName(
            $input->accountIdentifier(),
            self::WIKI_ADMINISTRATOR_PRINCIPAL_GROUP_NAME,
        );
        if ($wikiAdministratorPrincipalGroup === null) {
            $wikiAdministratorPrincipalGroup = $this->principalGroupFactory->create(
                $input->accountIdentifier(),
                self::WIKI_ADMINISTRATOR_PRINCIPAL_GROUP_NAME,
                false,
            );
        }

        $this->addRequiredRole($wikiAdministratorPrincipalGroup, self::WIKI_ADMINISTRATOR_ROLE);
        $this->addRequiredRole($wikiAdministratorPrincipalGroup, self::COLLABORATOR_ROLE);
        $this->principalGroupRepository->save($wikiAdministratorPrincipalGroup);

        return $wikiAdministratorPrincipalGroup;
    }

    private function addRequiredRole(PrincipalGroup $principalGroup, string $roleName): void
    {
        $role = $this->roleRepository->findByName($roleName);
        if ($role === null) {
            throw new SystemRoleNotFoundException($roleName);
        }

        $principalGroup->addRole($role->roleIdentifier());
    }

    private function isAccountOwner(CreatePrincipalInputPort $input): bool
    {
        $accountPrincipal = $this->accountPrincipalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $input->identityIdentifier(),
            $input->accountIdentifier(),
        );
        if ($accountPrincipal === null) {
            return false;
        }

        $ownerRole = $this->accountRoleRepository->findByName(AccountRole::OWNER);
        if ($ownerRole === null) {
            throw new RuntimeException('Owner account role is not found.');
        }

        $ownerGroup = $this->accountPrincipalGroupRepository->findByAccountIdAndRole(
            $input->accountIdentifier(),
            $ownerRole->roleIdentifier(),
        );

        return $ownerGroup?->hasMember($accountPrincipal->principalIdentifier()) ?? false;
    }
}
