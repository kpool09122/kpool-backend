<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ApproveAgencyInterface
{
    /**
     * @param ApproveAgencyInputPort $input
     * @param ApproveAgencyOutputPort $output
     * @return void
     * @throws AgencyNotFoundException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     * @throws InvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveAgencyInputPort $input, ApproveAgencyOutputPort $output): void;
}
