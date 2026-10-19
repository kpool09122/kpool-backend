<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Service;

use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\Exception\CannotRemoveLastWikiAdministratorException;
use Source\Wiki\Principal\Application\Service\PrincipalGroupChangeGuardInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PrincipalGroupManagerContinuity;

readonly class PrincipalGroupChangeGuard implements PrincipalGroupChangeGuardInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupManagerContinuity $principalGroupManagerContinuity,
        private PrincipalReachabilityInterface $principalReachability,
    ) {
    }

    /** @param PrincipalGroup[] $groups */
    public function assertPreserved(AccountIdentifier $accountIdentifier, array $groups): void
    {
        $identifiers = [];
        foreach ($groups as $group) {
            foreach ($group->members() as $identifier) {
                $identifiers[(string) $identifier] = $identifier;
            }
        }
        if (! $this->principalGroupManagerContinuity->hasManager(
            $groups,
            $this->principalRepository->findByIds(array_values($identifiers)),
            fn (Principal $principal): bool => $this->principalReachability->canAccess($principal->identityIdentifier(), $accountIdentifier)
        )) {
            throw new CannotRemoveLastWikiAdministratorException();
        }
    }
}
