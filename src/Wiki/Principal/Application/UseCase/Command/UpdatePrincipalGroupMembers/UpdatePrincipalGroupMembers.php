<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Wiki\Principal\Application\Exception\CannotRemoveLastWikiAdministratorException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class UpdatePrincipalGroupMembers implements UpdatePrincipalGroupMembersInterface
{
    private const string WIKI_ADMINISTRATOR_ROLE = 'WIKI_ADMINISTRATOR';

    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AccountPrincipalRepositoryInterface $accountPrincipalRepository,
    ) {
    }

    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void
    {
        if ($input->accountType() !== AccountType::CORPORATION) {
            throw new AccountUpdateForbiddenException();
        }

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

        $requestedPrincipalIdentifiers = [];
        $requestedGroupIds = [];
        foreach ($input->principalGroups() as $principalGroupInput) {
            $principalGroupId = (string) $principalGroupInput->principalGroupIdentifier();
            if (! isset($principalGroupsById[$principalGroupId])) {
                throw new PrincipalGroupNotFoundException();
            }

            $principalGroupsById[$principalGroupId]->replaceMembers($principalGroupInput->principalIdentifiers());
            $requestedGroupIds[$principalGroupId] = true;
            foreach ($principalGroupInput->principalIdentifiers() as $principalIdentifier) {
                $requestedPrincipalIdentifiers[(string) $principalIdentifier] = $principalIdentifier;
            }
        }

        $principalsById = $this->principalRepository->findByIds(array_values($requestedPrincipalIdentifiers));
        $this->assertPrincipalsBelongToAccount(array_values($requestedPrincipalIdentifiers), $principalsById, $input);
        $this->assertHasWikiAdministrator($principalGroups);

        foreach (array_keys($requestedGroupIds) as $principalGroupId) {
            $this->principalGroupRepository->save($principalGroupsById[$principalGroupId]);
        }

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
        return $this->accountPrincipalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $principal->identityIdentifier(),
            $input->accountIdentifier(),
        ) !== null;
    }

    /** @param array<int, PrincipalGroup> $principalGroups */
    private function assertHasWikiAdministrator(array $principalGroups): void
    {
        $wikiAdministratorRole = $this->roleRepository->findByName(self::WIKI_ADMINISTRATOR_ROLE);
        if ($wikiAdministratorRole === null) {
            throw new CannotRemoveLastWikiAdministratorException();
        }

        foreach ($principalGroups as $principalGroup) {
            if ($principalGroup->hasRole($wikiAdministratorRole->roleIdentifier()) && $principalGroup->memberCount() > 0) {
                return;
            }
        }

        throw new CannotRemoveLastWikiAdministratorException();
    }
}
