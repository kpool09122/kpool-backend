<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use Source\Identity\Domain\Exception\PasskeyRecoveryRequiredException;

interface CreateStepUpPasskeyOptionsInterface
{
    /** @throws PasskeyRecoveryRequiredException */
    public function process(CreateStepUpPasskeyOptionsInputPort $input, CreateStepUpPasskeyOptionsOutputPort $output): void;
}
