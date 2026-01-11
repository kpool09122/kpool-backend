<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation;

use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;

readonly class RejectAffiliation implements RejectAffiliationInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
    ) {
    }

    public function process(RejectAffiliationInputPort $input): void
    {
        $affiliation = $this->affiliationRepository->findById($input->affiliationIdentifier());

        if ($affiliation === null) {
            throw new AffiliationNotFoundException('Affiliation not found.');
        }

        if (! $affiliation->isPending()) {
            throw new DisallowedAffiliationOperationException('Only pending affiliations can be rejected.');
        }

        if ((string) $affiliation->approverAccountIdentifier() !== (string) $input->rejectorAccountIdentifier()) {
            throw new DisallowedAffiliationOperationException('Only the designated approver can reject this affiliation.');
        }

        $this->affiliationRepository->delete($affiliation);
    }
}
