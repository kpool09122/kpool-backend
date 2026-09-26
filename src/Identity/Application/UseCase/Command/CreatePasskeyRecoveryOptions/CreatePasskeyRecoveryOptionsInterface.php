<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

interface CreatePasskeyRecoveryOptionsInterface
{
    public function process(CreatePasskeyRecoveryOptionsInputPort $input, CreatePasskeyRecoveryOptionsOutputPort $output): void;
}
