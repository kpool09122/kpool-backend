<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Exception\PrincipalAlreadyAssignedToPrincipalGroupException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class UpdatePrincipalGroupMembers implements UpdatePrincipalGroupMembersInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void
    {
        $accountIdentifier = $input->accountIdentifier();
        if ((string) $input->principal()->accountIdentifier() !== (string) $accountIdentifier
            || ! $this->policyEvaluator->evaluate(
                $input->principal(),
                Action::PRINCIPAL_GROUP_MANAGE,
                Resource::account($accountIdentifier, $input->accountType())
            )
        ) {
            throw new AccountUpdateForbiddenException();
        }

        $principalGroups = $this->principalGroupRepository->findByAccountId($accountIdentifier);
        $principalGroupsById = [];
        foreach ($principalGroups as $principalGroup) {
            $principalGroupsById[(string) $principalGroup->principalGroupIdentifier()] = $principalGroup;
        }

        $requestedPrincipalIdentifiersByGroupId = [];
        $allRequestedPrincipalIdentifiers = [];
        foreach ($input->principalGroups() as $principalGroupInput) {
            $principalGroupId = (string) $principalGroupInput->principalGroupIdentifier();
            if (! isset($principalGroupsById[$principalGroupId])) {
                throw new PrincipalGroupNotFoundException();
            }

            $requestedPrincipalIdentifiersByGroupId[$principalGroupId] = $principalGroupInput->principalIdentifiers();
            foreach ($principalGroupInput->principalIdentifiers() as $principalIdentifier) {
                $allRequestedPrincipalIdentifiers[(string) $principalIdentifier] = $principalIdentifier;
            }
        }

        foreach ($requestedPrincipalIdentifiersByGroupId as $principalGroupId => $principalIdentifiers) {
            $principalGroupsById[$principalGroupId]->replaceMembers($principalIdentifiers);
        }

        $this->assertPrincipalsAssignedToSingleGroup($principalGroups);

        $principalsById = $this->principalRepository->findByIds(array_values($this->collectPrincipalIdentifiers($principalGroups)));
        $this->assertPrincipalsBelongToAccount(array_values($allRequestedPrincipalIdentifiers), $principalsById, $accountIdentifier);

        if (! $this->hasPrincipalGroupManager($principalGroups, $principalsById)) {
            throw new CannotRemoveLastPrincipalGroupManagerException();
        }

        foreach (array_keys($requestedPrincipalIdentifiersByGroupId) as $principalGroupId) {
            $this->principalGroupRepository->save($principalGroupsById[$principalGroupId]);
        }

        $output->setPrincipalGroups(array_values(array_intersect_key($principalGroupsById, $requestedPrincipalIdentifiersByGroupId)));
    }

    /**
     * @param array<int, PrincipalGroup> $principalGroups
     */
    private function assertPrincipalsAssignedToSingleGroup(array $principalGroups): void
    {
        $groupIdsByPrincipalId = [];
        foreach ($principalGroups as $principalGroup) {
            $principalGroupId = (string) $principalGroup->principalGroupIdentifier();
            foreach ($principalGroup->members() as $principalIdentifier) {
                $principalId = (string) $principalIdentifier;
                if (isset($groupIdsByPrincipalId[$principalId]) && $groupIdsByPrincipalId[$principalId] !== $principalGroupId) {
                    throw new PrincipalAlreadyAssignedToPrincipalGroupException();
                }

                $groupIdsByPrincipalId[$principalId] = $principalGroupId;
            }
        }
    }

    /**
     * @param array<int, PrincipalIdentifier> $principalIdentifiers
     * @param array<string, Principal> $principalsById
     */
    private function assertPrincipalsBelongToAccount(array $principalIdentifiers, array $principalsById, AccountIdentifier $accountIdentifier): void
    {
        foreach ($principalIdentifiers as $principalIdentifier) {
            $principal = $principalsById[(string) $principalIdentifier] ?? null;
            if ($principal === null || (string) $principal->accountIdentifier() !== (string) $accountIdentifier) {
                throw new PrincipalNotFoundException();
            }
        }
    }

    /**
     * @param array<int, PrincipalGroup> $principalGroups
     * @param array<string, Principal> $principalsById
     */
    private function hasPrincipalGroupManager(array $principalGroups, array $principalsById): bool
    {
        $roleIdentifiersByPrincipalId = $this->collectRoleIdentifiersByPrincipalId($principalGroups, $principalsById);
        if (empty($roleIdentifiersByPrincipalId)) {
            return false;
        }

        $roles = $this->roleRepository->findByIds(array_values($this->flattenUnique($roleIdentifiersByPrincipalId)));
        $policyIdentifiersByPrincipalId = $this->collectPolicyIdentifiersByPrincipalId($roleIdentifiersByPrincipalId, $roles);
        if (empty($policyIdentifiersByPrincipalId)) {
            return false;
        }

        $policies = $this->policyRepository->findByIds(array_values($this->flattenUnique($policyIdentifiersByPrincipalId)));

        return array_any($policyIdentifiersByPrincipalId, fn ($policyIdentifiers) => $this->canManagePrincipalGroups($policyIdentifiers, $policies));
    }

    /**
     * @param array<int, PrincipalGroup> $principalGroups
     * @return array<string, PrincipalIdentifier>
     */
    private function collectPrincipalIdentifiers(array $principalGroups): array
    {
        $principalIdentifiers = [];
        foreach ($principalGroups as $principalGroup) {
            foreach ($principalGroup->members() as $principalIdentifier) {
                $principalIdentifiers[(string) $principalIdentifier] = $principalIdentifier;
            }
        }

        return $principalIdentifiers;
    }

    /**
     * @param array<int, PrincipalGroup> $principalGroups
     * @param array<string, Principal> $principalsById
     * @return array<string, array<string, \Source\Account\Principal\Domain\ValueObject\RoleIdentifier>>
     */
    private function collectRoleIdentifiersByPrincipalId(array $principalGroups, array $principalsById): array
    {
        $roleIdentifiersByPrincipalId = [];
        foreach ($principalGroups as $principalGroup) {
            foreach ($principalGroup->roles() as $roleIdentifier) {
                foreach ($principalGroup->members() as $principalIdentifier) {
                    $principalId = (string) $principalIdentifier;
                    if (! isset($principalsById[$principalId])) {
                        continue;
                    }
                    $roleIdentifiersByPrincipalId[$principalId][(string) $roleIdentifier] = $roleIdentifier;
                }
            }
        }

        return $roleIdentifiersByPrincipalId;
    }

    /**
     * @param array<string, array<string, \Source\Account\Principal\Domain\ValueObject\RoleIdentifier>> $roleIdentifiersByPrincipalId
     * @param array<string, \Source\Account\Principal\Domain\Entity\Role> $roles
     * @return array<string, array<string, \Source\Account\Principal\Domain\ValueObject\PolicyIdentifier>>
     */
    private function collectPolicyIdentifiersByPrincipalId(array $roleIdentifiersByPrincipalId, array $roles): array
    {
        $policyIdentifiersByPrincipalId = [];
        foreach ($roleIdentifiersByPrincipalId as $principalId => $roleIdentifiers) {
            foreach ($roleIdentifiers as $roleIdentifier) {
                $role = $roles[(string) $roleIdentifier] ?? null;
                if ($role === null) {
                    continue;
                }
                foreach ($role->policies() as $policyIdentifier) {
                    $policyIdentifiersByPrincipalId[$principalId][(string) $policyIdentifier] = $policyIdentifier;
                }
            }
        }

        return $policyIdentifiersByPrincipalId;
    }

    /**
     * @param array<string, \Source\Account\Principal\Domain\ValueObject\PolicyIdentifier> $policyIdentifiers
     * @param array<string, \Source\Account\Principal\Domain\Entity\Policy> $policies
     */
    private function canManagePrincipalGroups(array $policyIdentifiers, array $policies): bool
    {
        $statements = [];
        foreach ($policyIdentifiers as $policyIdentifier) {
            $policy = $policies[(string) $policyIdentifier] ?? null;
            if ($policy === null) {
                continue;
            }
            array_push($statements, ...$policy->statements());
        }

        $applicableStatements = array_filter(
            $statements,
            static fn (Statement $statement): bool =>
            in_array(Action::PRINCIPAL_GROUP_MANAGE, $statement->actions(), true)
            && in_array(ResourceType::ACCOUNT, $statement->resourceTypes(), true)
        );

        if (array_any($applicableStatements, static fn (Statement $statement): bool => $statement->effect() === Effect::DENY)) {
            return false;
        }

        return array_any($applicableStatements, static fn (Statement $statement): bool => $statement->effect() === Effect::ALLOW);
    }

    /**
     * @template T
     * @param array<string, array<string, T>> $itemsByOwnerId
     * @return array<string, T>
     */
    private function flattenUnique(array $itemsByOwnerId): array
    {
        $items = [];
        foreach ($itemsByOwnerId as $ownerItems) {
            foreach ($ownerItems as $itemId => $item) {
                $items[$itemId] = $item;
            }
        }

        return $items;
    }
}
