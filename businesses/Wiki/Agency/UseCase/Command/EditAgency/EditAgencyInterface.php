<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Command\EditAgency;

use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\UseCase\Exception\AgencyNotFoundException;

interface EditAgencyInterface
{
    /**
     * @param EditAgencyInputPort $input
     * @return Agency
     * @throws AgencyNotFoundException
     */
    public function process(EditAgencyInputPort $input): Agency;
}
