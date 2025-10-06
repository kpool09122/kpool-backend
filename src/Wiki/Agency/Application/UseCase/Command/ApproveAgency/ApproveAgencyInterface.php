<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface ApproveAgencyInterface
{
    /**
     * @param ApproveAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(ApproveAgencyInputPort $input): DraftAgency;
}
