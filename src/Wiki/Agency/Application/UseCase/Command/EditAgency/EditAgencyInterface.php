<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\EditAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;

interface EditAgencyInterface
{
    /**
     * @param EditAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     */
    public function process(EditAgencyInputPort $input): DraftAgency;
}
