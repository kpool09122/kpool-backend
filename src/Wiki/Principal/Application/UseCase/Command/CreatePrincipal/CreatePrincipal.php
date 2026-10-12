<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;
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
    private const string COLLABORATOR_ROLE = 'Collaborator';
    private const string WIKI_ADMINISTRATOR_ROLE = 'Administrator';

    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private RoleRepositoryInterface $roleRepository,
        private AccountPrincipalRepositoryInterface $accountPrincipalRepository,
    ) {
    }

    /**
     * @param CreatePrincipalInputPort $input
     * @param CreatePrincipalOutputPort $output
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input, CreatePrincipalOutputPort $output): void
    {
        $existingPrincipal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $input->identityIdentifier(),
            $input->accountIdentifier(),
        );

        if ($existingPrincipal !== null) {
            throw new PrincipalAlreadyExistsException();
        }

        $principal = $this->principalFactory->create(
            $input->identityIdentifier(),
            $input->accountIdentifier(),
        );

        $this->principalRepository->save($principal);

        if ($this->isAccountOwner($input)) {
            $administratorGroup = $this->findOrCreateWikiAdministratorPrincipalGroup($input);
            $administratorGroup->addMember($principal->principalIdentifier());
            $this->principalGroupRepository->save($administratorGroup);
            foreach ($this->principalGroupRepository->findInitialOwnerGroupsByAccountId($input->accountIdentifier()) as $principalGroup) {
                $principalGroup->addMember($principal->principalIdentifier());
                $this->principalGroupRepository->save($principalGroup);
            }
        }

        $defaultGroup = $this->findOrCreateDefaultPrincipalGroup($input);
        $defaultGroup->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($defaultGroup);

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
        $defaultPrincipalGroup->setDescription(new PrincipalGroupDescription('Creates and edits Wiki drafts, submits them for review, and deletes or withdraws their own drafts.'));

        $this->addRequiredRole($defaultPrincipalGroup, self::COLLABORATOR_ROLE);

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
            $wikiAdministratorPrincipalGroup->setDescription(new PrincipalGroupDescription('Manages Wiki user groups and their memberships.'));
        }

        $this->addRequiredRole($wikiAdministratorPrincipalGroup, self::WIKI_ADMINISTRATOR_ROLE);
        $this->principalGroupRepository->save($wikiAdministratorPrincipalGroup);

        return $wikiAdministratorPrincipalGroup;
    }

    private function addRequiredRole(PrincipalGroup $principalGroup, string $roleName): void
    {
        $role = $this->roleRepository->findSystemByName($roleName);
        if ($role === null) {
            throw new SystemRoleNotFoundException($roleName);
        }

        $principalGroup->addRole($role);
    }

    private function isAccountOwner(CreatePrincipalInputPort $input): bool
    {
        return array_any(
            $this->accountPrincipalRepository->findOwnersByAccountId($input->accountIdentifier()),
            static fn (AccountPrincipal $owner): bool => (string) $owner->identityIdentifier() === (string) $input->identityIdentifier(),
        );
    }
}
