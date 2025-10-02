<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AgencyServiceInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class ApproveAgency implements ApproveAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
        private AgencyServiceInterface $agencyService,
    ) {
    }

    /**
     * @param ApproveAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     * @throws InvalidStatusException
     */
    public function process(ApproveAgencyInputPort $input): DraftAgency
    {
        $agency = $this->agencyRepository->findDraftById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        if ($agency->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->agencyService->existsApprovedButNotTranslatedAgency(
            $agency->translationSetIdentifier(),
            $agency->agencyIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedAgencyException();
        }

        $agency->setStatus(ApprovalStatus::Approved);

        $this->agencyRepository->saveDraft($agency);

        return $agency;
    }
}
