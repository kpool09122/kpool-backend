<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Monetization\Principal\Application\Exception\AccountMembershipRequiredException;
use Source\Monetization\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Monetization\Principal\Application\Service\AccountMembershipServiceInterface;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Monetization\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Monetization\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class CreatePrincipal implements CreatePrincipalInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private RoleRepositoryInterface $roleRepository,
        private AccountMembershipServiceInterface $accountMembershipService,
    ) {
    }

    public function process(CreatePrincipalInputPort $input, CreatePrincipalOutputPort $output): void
    {
        if (! $this->accountMembershipService->belongsToActiveAccount($input->identityIdentifier(), $input->accountIdentifier())) {
            throw new AccountMembershipRequiredException();
        }
        if ($this->principalRepository->findByIdentityIdentifierAndAccountIdentifier($input->identityIdentifier(), $input->accountIdentifier()) !== null) {
            throw new PrincipalAlreadyExistsException();
        }
        $group = $this->principalGroupRepository->findDefaultByAccountIdentifier($input->accountIdentifier());
        if ($group === null) {
            $role = $this->roleRepository->findSystemByName(Role::GENERAL) ?? throw new SystemRoleNotFoundException(Role::GENERAL);
            $group = $this->principalGroupFactory->create(PrincipalGroup::GENERAL_GROUP_NAME, [$role->roleIdentifier()], $input->accountIdentifier(), true);
        }
        $principal = $this->principalFactory->create($input->identityIdentifier(), $input->accountIdentifier());
        $this->principalRepository->save($principal);
        $group->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($group);
        if ($this->accountMembershipService->isAccountOwner($input->identityIdentifier(), $input->accountIdentifier())) {
            $ownerRole = $this->roleRepository->findSystemByName(Role::OWNER) ?? throw new SystemRoleNotFoundException(Role::OWNER);
            $ownerGroup = $this->principalGroupRepository->findByAccountIdAndName($input->accountIdentifier(), PrincipalGroup::OWNER_GROUP_NAME)
                ?? $this->principalGroupFactory->create(PrincipalGroup::OWNER_GROUP_NAME, [], $input->accountIdentifier());
            $ownerGroup->addRole($ownerRole);
            $ownerGroup->addMember($principal->principalIdentifier());
            $this->principalGroupRepository->save($ownerGroup);
        }
        $output->setPrincipal($principal);
    }
}
