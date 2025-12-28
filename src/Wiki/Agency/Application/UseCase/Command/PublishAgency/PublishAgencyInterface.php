<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface PublishAgencyInterface
{
    /**
     * @param PublishAgencyInputPort $input
     * @return Agency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishAgencyInputPort $input): Agency;
}
