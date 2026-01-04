<?php

declare(strict_types=1);

namespace Source\Account\Domain\Service;

use Source\Account\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;

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
