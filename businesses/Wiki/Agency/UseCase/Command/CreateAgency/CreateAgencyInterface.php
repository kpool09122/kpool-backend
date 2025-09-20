<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Command\CreateAgency;

use Businesses\Wiki\Agency\Domain\Entity\Agency;

interface CreateAgencyInterface
{
    public function process(CreateAgencyInputPort $input): Agency;
}
