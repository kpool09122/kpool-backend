<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ReachablePrincipalGroupManagerInterface
{
    /**
     * Accounts without principal groups do not require a manager.
     * Evaluates the caller's proposed reachable identities synchronously, without persisting changes.
     *
     * @param IdentityIdentifier[] $reachableIdentityIdentifiers
     */
    public function isPreserved(AccountIdentifier $accountIdentifier, array $reachableIdentityIdentifiers): bool;
}
