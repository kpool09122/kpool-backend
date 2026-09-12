<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Identity\Application\Service\DelegationValidatorInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

readonly class DelegationValidator implements DelegationValidatorInterface
{
    public function __construct(
        private DelegationRepositoryInterface $delegationRepository,
        private AffiliationRepositoryInterface $affiliationRepository,
    ) {
    }

    public function isValid(DelegationIdentifier $delegationIdentifier): bool
    {
        $delegation = $this->delegationRepository->findById($delegationIdentifier);

        if ($delegation === null) {
            return false;
        }

        if (! $delegation->isApproved()) {
            return false;
        }

        $affiliation = $this->affiliationRepository->findById($delegation->affiliationIdentifier());

        if ($affiliation === null) {
            return false;
        }

        return $affiliation->isActive();
    }
}
