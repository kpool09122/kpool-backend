<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class ApproveAffiliation implements ApproveAffiliationInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
        private EventDispatcherInterface $eventDispatcher,
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

        $this->eventDispatcher->dispatch(new AffiliationActivated(
            $affiliation->affiliationIdentifier(),
            $affiliation->agencyAccountIdentifier(),
            $affiliation->talentAccountIdentifier(),
            $affiliation->activatedAt(),
        ));

        return $affiliation;
    }
}
