<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Shared\Domain\ValueObject\DelegationIdentifier;

interface DelegationValidatorInterface
{
    /**
     * Check if a delegation is valid (APPROVED status and related affiliation is ACTIVE).
     */
    public function isValid(DelegationIdentifier $delegationIdentifier): bool;
}
