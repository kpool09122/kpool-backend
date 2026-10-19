<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Service;

use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Service\ChildPrincipalManagerContinuityInterface;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Application\Service\ReachablePrincipalGroupManagerInterface as SiteManagementReachablePrincipalGroupManagerInterface;
use Source\Wiki\Principal\Application\Service\ReachablePrincipalGroupManagerInterface as WikiReachablePrincipalGroupManagerInterface;

readonly class ChildPrincipalManagerContinuity implements ChildPrincipalManagerContinuityInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalReachabilityInterface $principalReachability,
        private WikiReachablePrincipalGroupManagerInterface $wikiReachablePrincipalGroupManager,
        private SiteManagementReachablePrincipalGroupManagerInterface $siteManagementReachablePrincipalGroupManager,
    ) {
    }

    /** @param array<string, Principal> $principalsById
     * @param PrincipalGroup[]|null $principalGroups */
    public function assertPreserved(AccountIdentifier $accountIdentifier, array $principalsById, ?array $principalGroups = null): void
    {
        $affectedAccounts = [(string) $accountIdentifier => $accountIdentifier];
        $identityIdentifiers = [];
        foreach ($this->principalRepository->findByAccountId($accountIdentifier) as $principal) {
            $identityIdentifiers[(string) $principal->identityIdentifier()] = $principal->identityIdentifier();
        }
        foreach ($this->principalRepository->findAllByIdentityIdentifiers(array_values($identityIdentifiers)) as $related) {
            $affectedAccounts[(string) $related->accountIdentifier()] = $related->accountIdentifier();
        }
        $principals = $this->principalRepository->findByAccountIds(array_values($affectedAccounts));
        $reachableIdentitiesByAccount = [];
        foreach ($this->principalReachability->filterReachable($principals, $principalsById, $principalGroups, $accountIdentifier) as $principal) {
            $reachableIdentitiesByAccount[(string) $principal->accountIdentifier()][(string) $principal->identityIdentifier()] = $principal->identityIdentifier();
        }
        foreach ($affectedAccounts as $affectedAccount) {
            $reachableIdentityIds = array_values($reachableIdentitiesByAccount[(string) $affectedAccount] ?? []);
            if (! $this->wikiReachablePrincipalGroupManager->isPreserved($affectedAccount, $reachableIdentityIds)) {
                throw new CannotRemoveLastPrincipalGroupManagerException('The last reachable Wiki manager cannot be removed.');
            }
            if (! $this->siteManagementReachablePrincipalGroupManager->isPreserved($affectedAccount, $reachableIdentityIds)) {
                throw new CannotRemoveLastPrincipalGroupManagerException('The last reachable SiteManagement manager cannot be removed.');
            }
        }
    }
}
