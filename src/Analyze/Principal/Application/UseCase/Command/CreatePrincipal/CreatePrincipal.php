<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Analyze\Principal\Application\Exception\AccountMembershipRequiredException;
use Source\Analyze\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Analyze\Principal\Application\Service\AccountMembershipServiceInterface;
use Source\Analyze\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Analyze\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Analyze\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\RoleRepositoryInterface;

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
            $role = $this->roleRepository->findSystemByName('General') ?? throw new SystemRoleNotFoundException('General');
            $group = $this->principalGroupFactory->create('General', [$role->roleIdentifier()], $input->accountIdentifier(), true);
        }
        $principal = $this->principalFactory->create($input->identityIdentifier(), $input->accountIdentifier());
        $this->principalRepository->save($principal);
        $group->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($group);
        $output->setPrincipal($principal);
    }
}
