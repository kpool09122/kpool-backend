<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;

class CreateAgency implements CreateAgencyInterface
{
    public function __construct(
        private AgencyFactoryInterface $agencyFactory,
        private AgencyRepositoryInterface $agencyRepository,
    ) {
    }

    public function process(CreateAgencyInputPort $input): Agency
    {
        $agency = $this->agencyFactory->create(
            $input->translation(),
            $input->name(),
        );
        $agency->setCEO($input->CEO());
        if ($input->foundedIn()) {
            $agency->setFoundedIn($input->foundedIn());
        }
        $agency->setDescription($input->description());

        $this->agencyRepository->save($agency);

        return $agency;
    }
}
