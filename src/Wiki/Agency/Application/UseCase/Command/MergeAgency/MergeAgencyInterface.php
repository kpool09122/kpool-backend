<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\MergeAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface MergeAgencyInterface
{
    /**
     * @param MergeAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeAgencyInputPort $input): DraftAgency;
}
