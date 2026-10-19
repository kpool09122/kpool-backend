<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use InvalidArgumentException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Application\Service\ChildPrincipalManagerContinuityInterface;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class UpdatePrincipalGroupMembers implements UpdatePrincipalGroupMembersInterface
{
    public function __construct(
        private ChildPrincipalManagerContinuityInterface $childPrincipalManagerContinuity,
        private PrincipalReachabilityInterface $principalReachability,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void
    {
        $accountIdentifier = $input->accountIdentifier();
        $account = $this->accountRepository->findById($accountIdentifier);
        if ($account === null) {
            throw new AccountUpdateForbiddenException();
        }
        $resource = Resource::account($accountIdentifier, $account->type(), $account->accountCategory());
        if ((string) $input->principal()->accountIdentifier() !== (string) $accountIdentifier
            || ! $this->policyEvaluator->evaluate($input->principal(), Action::PRINCIPAL_GROUP_MANAGE, $resource)) {
            throw new AccountUpdateForbiddenException();
        }
        $groupsById = [];
        foreach ($this->principalGroupRepository->findByAccountId($accountIdentifier) as $group) {
            $groupsById[(string) $group->principalGroupIdentifier()] = $group;
        }
        $requested = [];
        $requestedIdentifiers = [];
        foreach ($input->principalGroups() as $groupInput) {
            $id = (string) $groupInput->principalGroupIdentifier();
            if (isset($requested[$id])) {
                throw new InvalidArgumentException('Duplicate principal group identifier.');
            }
            $group = $groupsById[$id] ?? throw new PrincipalGroupNotFoundException();
            $group->replaceMembers($groupInput->principalIdentifiers());
            $requested[$id] = true;
            foreach ($groupInput->principalIdentifiers() as $identifier) {
                $requestedIdentifiers[(string) $identifier] = $identifier;
            }
        }
        $statusIdentifiers = [];
        foreach ($input->principalStatuses() as $update) {
            $id = (string) $update->principalIdentifier;
            if (isset($statusIdentifiers[$id])) {
                throw new InvalidArgumentException('Duplicate principal status identifier.');
            }
            $statusIdentifiers[$id] = $update->principalIdentifier;
        }
        $identifiers = $requestedIdentifiers + $statusIdentifiers;
        foreach ($groupsById as $group) {
            foreach ($group->members() as $identifier) {
                $identifiers[(string) $identifier] = $identifier;
            }
        }
        $principalsById = $this->principalRepository->findByIds(array_values($identifiers));
        foreach ($requestedIdentifiers + $statusIdentifiers as $id => $identifier) {
            $principal = $principalsById[$id] ?? null;
            if ($principal === null || (string) $principal->accountIdentifier() !== (string) $accountIdentifier) {
                throw new PrincipalNotFoundException();
            }
        }
        foreach ($input->principalStatuses() as $update) {
            $principalsById[(string) $update->principalIdentifier]->changeStatus($update->status);
        }
        $reachablePrincipals = $this->principalReachability->filterReachable(array_values($principalsById), $principalsById, array_values($groupsById), $accountIdentifier);
        if (! (new PrincipalGroupManagerContinuity($this->roleRepository, $this->policyRepository))->hasManager(array_values($groupsById), $reachablePrincipals, $resource)) {
            throw new CannotRemoveLastPrincipalGroupManagerException();
        }
        $this->childPrincipalManagerContinuity->assertPreserved($accountIdentifier, $principalsById, array_values($groupsById));
        foreach (array_keys($requested) as $id) {
            // All continuity checks run before either memberships or statuses are persisted.
            $this->principalGroupRepository->save($groupsById[$id]);
        }
        foreach ($statusIdentifiers as $id => $identifier) {
            $this->principalRepository->save($principalsById[$id]);
        }
        $output->setPrincipalStatuses(array_values(array_intersect_key($principalsById, $statusIdentifiers)));
        $output->setPrincipalGroups(array_values(array_intersect_key($groupsById, $requested)));
    }
}
