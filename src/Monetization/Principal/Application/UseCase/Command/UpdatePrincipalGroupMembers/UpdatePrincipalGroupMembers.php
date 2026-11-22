<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use InvalidArgumentException;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Monetization\Principal\Application\Exception\CannotRemoveLastMonetizationAdministratorException;
use Source\Monetization\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Monetization\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Monetization\Principal\Application\Service\AccountMembershipServiceInterface;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\Monetization\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

readonly class UpdatePrincipalGroupMembers implements UpdatePrincipalGroupMembersInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AccountMembershipServiceInterface $accountMembershipService,
        private PrincipalReachabilityInterface $principalReachability,
    ) {
    }

    /**
     * @throws UnauthorizedException
     * @throws InvalidArgumentException
     * @throws PrincipalGroupNotFoundException
     * @throws PrincipalNotFoundException
     * @throws PrincipalStatusChangeForbiddenException
     * @throws CannotRemoveLastMonetizationAdministratorException
     */
    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void
    {
        $operator = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($operator === null || ! $this->belongsToAccount($operator, $input) || ! $this->principalReachability->canAccess($operator->identityIdentifier(), $input->accountIdentifier())) {
            throw new UnauthorizedException();
        }

        if (! $this->policyEvaluator->evaluate($operator, Action::PRINCIPAL_GROUP_MANAGE, new Resource(ResourceType::PRINCIPAL_GROUP, $input->accountIdentifier()))) {
            throw new UnauthorizedException();
        }

        $principalGroups = $this->principalGroupRepository->findByAccountId($input->accountIdentifier());
        $principalGroupsById = [];
        foreach ($principalGroups as $principalGroup) {
            $principalGroupsById[(string) $principalGroup->principalGroupIdentifier()] = $principalGroup;
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


            $requestedGroupIds[$principalGroupId] = true;
            $memberIds = [];
            foreach ($principalGroupInput->principalIdentifiers() as $principalIdentifier) {
                if (isset($memberIds[(string) $principalIdentifier])) {
                    throw new InvalidArgumentException('Duplicate group member identifier.');
                }
                $memberIds[(string) $principalIdentifier] = true;
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
            throw new CannotRemoveLastMonetizationAdministratorException();
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
            && $this->accountMembershipService->belongsToAccount($principal->identityIdentifier(), $input->accountIdentifier());
    }
}
