<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Service;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ChildPrincipalManagerContinuityInterface
{
    /** @param array<string, Principal> $principalsById
     * @param PrincipalGroup[]|null $principalGroups */
    public function assertPreserved(AccountIdentifier $accountIdentifier, array $principalsById, ?array $principalGroups = null): void;
}
