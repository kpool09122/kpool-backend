<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions;

use Throwable;

interface CreatePasskeyAuthenticationOptionsInterface
{
    /** @throws Throwable */
    public function process(
        CreatePasskeyAuthenticationOptionsInputPort $input,
        CreatePasskeyAuthenticationOptionsOutputPort $output,
    ): void;
}
