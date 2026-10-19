<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Service;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PrincipalReachabilityInterface
{
    /**
     * @param Principal[] $principals
     * @param array<string, Principal> $principalsById
     * @param PrincipalGroup[]|null $principalGroups
     * @return array<string, Principal>
     */
    public function filterReachable(array $principals, array $principalsById = [], ?array $principalGroups = null, ?AccountIdentifier $principalGroupsAccountIdentifier = null): array;

    /** @param array<string, Principal> $principalsById
     * @param PrincipalGroup[]|null $principalGroups */
    public function canAccess(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier, array $principalsById = [], ?array $principalGroups = null, ?AccountIdentifier $principalGroupsAccountIdentifier = null): bool;
}
