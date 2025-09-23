<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;

interface CreateAgencyInterface
{
    public function process(CreateAgencyInputPort $input): DraftAgency;
}
