<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use InvalidArgumentException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\CannotRemoveLastWikiAdministratorException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class UpdatePrincipalGroupMembers implements UpdatePrincipalGroupMembersInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AccountPrincipalRepositoryInterface $accountPrincipalRepository,
        private PrincipalReachabilityInterface $principalReachability,
    ) {
    }

    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void
    {
        $operator = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($operator === null || ! $this->belongsToAccount($operator, $input)) {
            throw new AccountUpdateForbiddenException();
        }

        if (! $this->policyEvaluator->evaluate($operator, Action::PRINCIPAL_GROUP_MANAGE, new Resource(ResourceType::PRINCIPAL_GROUP))) {
            throw new AccountUpdateForbiddenException();
        }

        $principalGroups = $this->principalGroupRepository->findByAccountId($input->accountIdentifier());
        $principalGroupsById = [];
        foreach ($principalGroups as $principalGroup) {
            $principalGroupsById[(string) $principalGroup->principalGroupIdentifier()] = $principalGroup;
        }

        $protectedRoles = [];
        foreach (['Operator', 'Collaborator', 'SeniorCollaborator'] as $roleName) {
            $role = $this->roleRepository->findSystemByName($roleName);
            if ($role !== null) {
                $protectedRoles[] = $role;
            }
        }

        $requestedPrincipalIdentifiers = [];
        $statusIdentifiers = [];
        foreach ($input->principalStatuses() as $update) {
            $id = (string) $update->principalIdentifier;
            if (isset($statusIdentifiers[$id])) {
                throw new InvalidArgumentException('Duplicate principal status identifier.');
            }
            $statusIdentifiers[$id] = $update->principalIdentifier;
            $requestedPrincipalIdentifiers[$id] = $update->principalIdentifier;
        }
        $requestedGroupIds = [];
        foreach ($input->principalGroups() as $principalGroupInput) {
            $principalGroupId = (string) $principalGroupInput->principalGroupIdentifier();
            if (isset($requestedGroupIds[$principalGroupId])) {
                throw new InvalidArgumentException('Duplicate principal group identifier.');
            }
            if (! isset($principalGroupsById[$principalGroupId])) {
                throw new PrincipalGroupNotFoundException();
            }

            $principalGroup = $principalGroupsById[$principalGroupId];
            if (array_any($protectedRoles, static fn (Role $role): bool => $principalGroup->hasRole($role->roleIdentifier()))) {
                throw new AccountUpdateForbiddenException();
            }

            $requestedGroupIds[$principalGroupId] = true;
            foreach ($principalGroupInput->principalIdentifiers() as $principalIdentifier) {
                $requestedPrincipalIdentifiers[(string) $principalIdentifier] = $principalIdentifier;
            }
        }

        foreach ($input->principalGroups() as $principalGroupInput) {
            $principalGroupsById[(string) $principalGroupInput->principalGroupIdentifier()]->replaceMembers($principalGroupInput->principalIdentifiers());
        }

        $allIdentifiers = $requestedPrincipalIdentifiers;
        foreach ($principalGroups as $group) {
            foreach ($group->members() as $identifier) {
                $allIdentifiers[(string) $identifier] = $identifier;
            }
        }
        $principalsById = $this->principalRepository->findByIds(array_values($allIdentifiers));
        $this->assertPrincipalsBelongToAccount(array_values($requestedPrincipalIdentifiers), $principalsById, $input);
        foreach ($input->principalStatuses() as $update) {
            $principalsById[(string) $update->principalIdentifier]->changeStatus($update->status);
        }
        if (! (new PrincipalGroupManagerContinuity($this->roleRepository, $this->policyEvaluator))->hasManager(
            $principalGroups,
            $principalsById,
            fn (Principal $principal): bool => $this->principalReachability->canAccess($principal->identityIdentifier(), $input->accountIdentifier()),
        )) {
            throw new CannotRemoveLastWikiAdministratorException();
        }

        foreach (array_keys($requestedGroupIds) as $principalGroupId) {
            $this->principalGroupRepository->save($principalGroupsById[$principalGroupId]);
        }

        foreach ($statusIdentifiers as $id => $identifier) {
            $this->principalRepository->save($principalsById[$id]);
        }
        $output->setPrincipalStatuses(array_values(array_intersect_key($principalsById, $statusIdentifiers)));
        $output->setPrincipalGroups(array_values(array_intersect_key($principalGroupsById, $requestedGroupIds)));
    }

    /**
     * @param array<int, PrincipalIdentifier> $principalIdentifiers
     * @param array<string, Principal> $principalsById
     */
    private function assertPrincipalsBelongToAccount(array $principalIdentifiers, array $principalsById, UpdatePrincipalGroupMembersInputPort $input): void
    {
        foreach ($principalIdentifiers as $principalIdentifier) {
            $principal = $principalsById[(string) $principalIdentifier] ?? null;
            if ($principal === null || ! $this->belongsToAccount($principal, $input)) {
                throw new PrincipalNotFoundException();
            }
        }
    }

    private function belongsToAccount(Principal $principal, UpdatePrincipalGroupMembersInputPort $input): bool
    {
        return (string) $principal->accountIdentifier() === (string) $input->accountIdentifier()
            && $this->accountPrincipalRepository->findByIdentityIdentifierAndAccountIdentifier(
                $principal->identityIdentifier(),
                $input->accountIdentifier(),
            ) !== null;
    }
}
