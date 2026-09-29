<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

interface AuthenticateWithPasskeyInterface
{
    public function process(
        AuthenticateWithPasskeyInputPort $input,
        AuthenticateWithPasskeyOutputPort $output,
    ): void;
}
