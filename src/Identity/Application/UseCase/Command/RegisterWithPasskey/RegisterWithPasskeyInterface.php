<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterWithPasskey;

interface RegisterWithPasskeyInterface
{
    public function process(RegisterWithPasskeyInputPort $input, RegisterWithPasskeyOutputPort $output): void;
}
