<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdatePasskey;

interface UpdatePasskeyInterface
{
    public function process(UpdatePasskeyInputPort $input, UpdatePasskeyOutputPort $output): void;
}
