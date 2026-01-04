<?php

declare(strict_types=1);

namespace Source\Account\Domain\Service;

use Source\Account\Domain\ValueObject\AffiliationIdentifier;

interface DelegationTerminationServiceInterface
{
    public function revokeAllDelegations(AffiliationIdentifier $affiliationIdentifier): int;
}
