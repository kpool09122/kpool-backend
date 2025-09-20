<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Command\CreateAgency;

use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Businesses\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;

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
