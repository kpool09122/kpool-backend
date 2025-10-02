<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface RejectAgencyInterface
{
    /**
     * @param RejectAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     */
    public function process(RejectAgencyInputPort $input): DraftAgency;
}
