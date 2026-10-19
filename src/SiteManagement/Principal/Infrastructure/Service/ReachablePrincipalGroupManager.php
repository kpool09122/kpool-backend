<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Infrastructure\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\Service\ReachablePrincipalGroupManagerInterface;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PrincipalGroupManagerContinuity;

readonly class ReachablePrincipalGroupManager implements ReachablePrincipalGroupManagerInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupManagerContinuity $principalGroupManagerContinuity,
    ) {
    }

    /** @param IdentityIdentifier[] $reachableIdentityIdentifiers */
    public function isPreserved(AccountIdentifier $accountIdentifier, array $reachableIdentityIdentifiers): bool
    {
        $groups = $this->principalGroupRepository->findByAccountId($accountIdentifier);
        if ($groups === []) {
            return true;
        }
        $principalIdentifiers = [];
        foreach ($groups as $group) {
            foreach ($group->members() as $identifier) {
                $principalIdentifiers[(string) $identifier] = $identifier;
            }
        }
        $reachableIdentities = [];
        foreach ($reachableIdentityIdentifiers as $identifier) {
            $reachableIdentities[(string) $identifier] = true;
        }

        return $this->principalGroupManagerContinuity->hasManager(
            $groups,
            $this->principalRepository->findByIds(array_values($principalIdentifiers)),
            static fn (Principal $principal): bool => isset($reachableIdentities[(string) $principal->identityIdentifier()]),
        );
    }
}
