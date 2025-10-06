<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface CreateAgencyInterface
{
    /**
     * @param CreateAgencyInputPort $input
     * @return DraftAgency
     * @throws UnauthorizedException
     */
    public function process(CreateAgencyInputPort $input): DraftAgency;
}
