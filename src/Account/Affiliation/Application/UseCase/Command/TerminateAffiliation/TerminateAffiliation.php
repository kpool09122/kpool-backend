<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation;

use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Delegation\Domain\Service\DelegationTerminationServiceInterface;

readonly class TerminateAffiliation implements TerminateAffiliationInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
        private DelegationTerminationServiceInterface $delegationTerminationService,
    ) {
    }

    public function process(TerminateAffiliationInputPort $input): Affiliation
    {
        $affiliation = $this->affiliationRepository->findById($input->affiliationIdentifier());

        if ($affiliation === null) {
            throw new AffiliationNotFoundException('Affiliation not found.');
        }

        $terminatorId = (string) $input->terminatorAccountIdentifier();
        $agencyId = (string) $affiliation->agencyAccountIdentifier();
        $talentId = (string) $affiliation->talentAccountIdentifier();

        if ($terminatorId !== $agencyId && $terminatorId !== $talentId) {
            throw new DisallowedAffiliationOperationException('Only the agency or talent can terminate this affiliation.');
        }

        $this->delegationTerminationService->revokeAllDelegations($affiliation->affiliationIdentifier());

        $affiliation->terminate();

        $this->affiliationRepository->save($affiliation);

        return $affiliation;
    }
}
