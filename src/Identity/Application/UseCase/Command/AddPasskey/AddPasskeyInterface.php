<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskey;

interface AddPasskeyInterface
{
    public function process(AddPasskeyInputPort $input, AddPasskeyOutputPort $output): void;
}
