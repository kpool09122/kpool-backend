<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface PublishAgencyInterface
{
    /**
     * @param PublishAgencyInputPort $input
     * @return Agency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     */
    public function process(PublishAgencyInputPort $input): Agency;
}
