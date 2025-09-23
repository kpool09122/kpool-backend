<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveUpdatedAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Application\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class ApproveUpdatedAgency implements ApproveUpdatedAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
        private AgencyServiceInterface $agencyService,
    ) {
    }

    /**
     * @param ApproveUpdatedAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     * @throws InvalidStatusException
     */
    public function process(ApproveUpdatedAgencyInputPort $input): DraftAgency
    {
        $agency = $this->agencyRepository->findDraftById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        if ($agency->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }


        if ($input->publishedAgencyIdentifier()) {
            if ($this->agencyService->existsApprovedButNotTranslatedAgency(
                $input->agencyIdentifier(),
                $input->publishedAgencyIdentifier(),
            )) {
                throw new ExistsApprovedButNotTranslatedAgencyException();
            }
        }


        $agency->setStatus(ApprovalStatus::Approved);

        $this->agencyRepository->saveDraft($agency);

        return $agency;
    }
}
