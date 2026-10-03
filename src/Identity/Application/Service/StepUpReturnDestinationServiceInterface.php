<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\ValueObject\StepUpReturnDestination;

interface StepUpReturnDestinationServiceInterface
{
    public function resolve(StepUpReturnDestination $destination): string;
}
