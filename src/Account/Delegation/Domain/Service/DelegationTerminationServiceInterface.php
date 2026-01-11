<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Service;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;

interface DelegationTerminationServiceInterface
{
    public function revokeAllDelegations(AffiliationIdentifier $affiliationIdentifier): int;
}
