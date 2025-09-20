<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\EditAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;

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
