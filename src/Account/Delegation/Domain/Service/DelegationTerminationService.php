<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Service;

use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;

readonly class DelegationTerminationService implements DelegationTerminationServiceInterface
{
    public function __construct(
        private DelegationRepositoryInterface $delegationRepository,
    ) {
    }

    public function revokeAllDelegations(AffiliationIdentifier $affiliationIdentifier): int
    {
        $delegations = $this->delegationRepository->findApprovedByAffiliation($affiliationIdentifier);

        $revokedCount = 0;
        foreach ($delegations as $delegation) {
            $delegation->revoke();
            $this->delegationRepository->save($delegation);
            $revokedCount++;
        }

        return $revokedCount;
    }
}
