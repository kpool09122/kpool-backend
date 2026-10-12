<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use InvalidArgumentException;
use Source\SiteManagement\Principal\Application\Exception\CannotRemoveLastAdministratorException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalNotFoundException;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class UpdatePrincipalGroupMembers implements UpdatePrincipalGroupMembersInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /** @throws CannotRemoveLastAdministratorException
     * @throws PrincipalGroupNotFoundException
     * @throws PrincipalNotFoundException
     * @throws UnauthorizedException */
    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void
    {
        $operator = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($operator === null || (string) $operator->accountIdentifier() !== (string) $input->accountIdentifier()
            || ! $this->policyEvaluator->evaluate($operator, Action::PRINCIPAL_GROUP_MANAGE, new Resource(ResourceType::PRINCIPAL_GROUP))) {
            throw new UnauthorizedException();
        }

        $operatorRole = $this->roleRepository->findSystemByName('Operator');
        $administratorRole = $this->roleRepository->findSystemByName('Administrator');
        $groupsById = [];
        foreach ($this->principalGroupRepository->findByAccountId($input->accountIdentifier()) as $group) {
            if ((string) $group->accountIdentifier() === (string) $input->accountIdentifier()) {
                $groupsById[(string) $group->principalGroupIdentifier()] = $group;
            }
        }
        $requested = [];
        $principalIdentifiers = [];
        foreach ($input->principalGroups() as $groupInput) {
            $id = (string) $groupInput->principalGroupIdentifier();
            if (isset($requested[$id])) {
                throw new InvalidArgumentException('Duplicate principal group identifier.');
            }
            $group = $groupsById[$id] ?? throw new PrincipalGroupNotFoundException();
            if ($operatorRole !== null && $group->hasRole($operatorRole->roleIdentifier())) {
                throw new UnauthorizedException('Operator group membership cannot be edited.');
            }
            $members = [];
            foreach ($groupInput->principalIdentifiers() as $principalIdentifier) {
                $principalId = (string) $principalIdentifier;
                if (isset($members[$principalId])) {
                    throw new InvalidArgumentException('Duplicate principal identifier in a group.');
                }
                $members[$principalId] = $principalIdentifier;
                $principalIdentifiers[$principalId] = $principalIdentifier;
            }
            $requested[$id] = $members;
        }
        // Include omitted groups when validating the surviving administrator.
        foreach ($groupsById as $id => $group) {
            if ($administratorRole !== null && $group->hasRole($administratorRole->roleIdentifier()) && ($operatorRole === null || ! $group->hasRole($operatorRole->roleIdentifier()))) {
                foreach ($requested[$id] ?? $group->members() as $member) {
                    $principalIdentifiers[(string) $member] = $member;
                }
            }
        }
        $principalsById = $this->principalRepository->findByIds(array_values($principalIdentifiers));
        $validPrincipals = [];
        foreach ($principalsById as $principal) {
            if ((string) $principal->accountIdentifier() === (string) $input->accountIdentifier()) {
                $validPrincipals[(string) $principal->principalIdentifier()] = true;
            }
        }
        foreach ($requested as $members) {
            foreach ($members as $member) {
                if (! isset($validPrincipals[(string) $member])) {
                    throw new PrincipalNotFoundException();
                }
            }
        }
        $hasAdministrator = false;
        foreach ($groupsById as $id => $group) {
            if ($administratorRole === null || ! $group->hasRole($administratorRole->roleIdentifier()) || ($operatorRole !== null && $group->hasRole($operatorRole->roleIdentifier()))) {
                continue;
            }
            foreach ($requested[$id] ?? $group->members() as $member) {
                if (isset($validPrincipals[(string) $member])) {
                    $hasAdministrator = true;

                    break 2;
                }
            }
        }
        if (! $hasAdministrator) {
            throw new CannotRemoveLastAdministratorException();
        }
        foreach ($requested as $id => $members) {
            $groupsById[$id]->replaceMembers(array_values($members));
            $this->principalGroupRepository->save($groupsById[$id]);
        }
        $output->setPrincipalGroups(array_values(array_intersect_key($groupsById, $requested)));
    }
}
