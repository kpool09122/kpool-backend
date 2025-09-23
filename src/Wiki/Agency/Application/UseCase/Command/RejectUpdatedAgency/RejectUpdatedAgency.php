<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectUpdatedAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class RejectUpdatedAgency implements RejectUpdatedAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
    ) {
    }

    /**
     * @param RejectUpdatedAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function process(RejectUpdatedAgencyInputPort $input): DraftAgency
    {
        $agency = $this->agencyRepository->findDraftById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        if ($agency->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $agency->setStatus(ApprovalStatus::Rejected);

        $this->agencyRepository->saveDraft($agency);

        return $agency;
    }
}
