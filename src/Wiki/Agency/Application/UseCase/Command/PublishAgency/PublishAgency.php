<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Application\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class PublishAgency implements PublishAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
        private AgencyServiceInterface $agencyService,
        private AgencyFactoryInterface $agencyFactory,
    ) {
    }

    /**
     * @param PublishAgencyInputPort $input
     * @return Agency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     */
    public function process(PublishAgencyInputPort $input): Agency
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


        if ($agency->publishedAgencyIdentifier()) {
            $publishedAgency = $this->agencyRepository->findById($input->publishedAgencyIdentifier());
            if ($publishedAgency === null) {
                throw new AgencyNotFoundException();
            }
            $publishedAgency->setName($agency->name());
        } else {
            $publishedAgency = $this->agencyFactory->create(
                $agency->translation(),
                $agency->name(),
            );
        }
        $publishedAgency->setCEO($agency->CEO());
        $publishedAgency->setDescription($agency->description());
        $publishedAgency->setFoundedIn($agency->foundedIn());

        $this->agencyRepository->save($publishedAgency);
        $this->agencyRepository->deleteDraft($agency);

        return $publishedAgency;
    }
}
