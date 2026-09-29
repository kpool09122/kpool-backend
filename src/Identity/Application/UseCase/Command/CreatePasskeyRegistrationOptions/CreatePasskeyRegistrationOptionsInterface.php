<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

interface CreatePasskeyRegistrationOptionsInterface
{
    public function process(
        CreatePasskeyRegistrationOptionsInputPort $input,
        CreatePasskeyRegistrationOptionsOutputPort $output,
    ): void;
}
