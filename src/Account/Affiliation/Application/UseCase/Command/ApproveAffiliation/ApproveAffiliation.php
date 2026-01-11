<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;

readonly class ApproveAffiliation implements ApproveAffiliationInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
    ) {
    }

    public function process(ApproveAffiliationInputPort $input): Affiliation
    {
        $affiliation = $this->affiliationRepository->findById($input->affiliationIdentifier());

        if ($affiliation === null) {
            throw new AffiliationNotFoundException('Affiliation not found.');
        }

        if ((string) $affiliation->approverAccountIdentifier() !== (string) $input->approverAccountIdentifier()) {
            throw new DisallowedAffiliationOperationException('Only the designated approver can approve this affiliation.');
        }

        $affiliation->approve();

        $this->affiliationRepository->save($affiliation);

        return $affiliation;
    }
}
