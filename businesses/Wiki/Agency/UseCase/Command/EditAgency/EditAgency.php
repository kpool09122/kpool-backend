<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Command\EditAgency;

use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Businesses\Wiki\Agency\UseCase\Exception\AgencyNotFoundException;

class EditAgency implements EditAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
    ) {
    }

    /**
     * @param EditAgencyInputPort $input
     * @return Agency
     * @throws AgencyNotFoundException
     */
    public function process(EditAgencyInputPort $input): Agency
    {
        $agency = $this->agencyRepository->findById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        $agency->setName($input->name());
        $agency->setCEO($input->CEO());
        if ($input->foundedIn()) {
            $agency->setFoundedIn($input->foundedIn());
        }
        $agency->setDescription($input->description());

        $this->agencyRepository->save($agency);

        return $agency;
    }
}
