<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Source\Identity\Application\Service\StepUpReturnDestinationServiceInterface;
use Source\Identity\Domain\ValueObject\StepUpReturnDestination;

readonly class StepUpReturnDestinationService implements StepUpReturnDestinationServiceInterface
{
    public function resolve(StepUpReturnDestination $destination): string
    {
        return match ($destination) {
            StepUpReturnDestination::PASSKEYS => '/settings/passkeys?stepUp=complete',
            StepUpReturnDestination::WITHDRAWAL => '/settings/withdrawal?stepUp=complete',
        };
    }
}
