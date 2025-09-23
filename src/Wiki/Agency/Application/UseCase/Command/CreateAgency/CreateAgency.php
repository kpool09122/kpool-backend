<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;

class CreateAgency implements CreateAgencyInterface
{
    public function __construct(
        private DraftAgencyFactoryInterface $agencyFactory,
        private AgencyRepositoryInterface   $agencyRepository,
    ) {
    }

    public function process(CreateAgencyInputPort $input): DraftAgency
    {

        $agency = $this->agencyFactory->create(
            $input->editorIdentifier(),
            $input->translation(),
            $input->name(),
        );
        $publishedAgency = $this->agencyRepository->findById($input->publishedAgencyIdentifier());
        if ($publishedAgency) {
            $agency->setPublishedAgencyIdentifier($publishedAgency->agencyIdentifier());
        }
        $agency->setCEO($input->CEO());
        if ($input->foundedIn()) {
            $agency->setFoundedIn($input->foundedIn());
        }
        $agency->setDescription($input->description());

        $this->agencyRepository->saveDraft($agency);

        return $agency;
    }
}
