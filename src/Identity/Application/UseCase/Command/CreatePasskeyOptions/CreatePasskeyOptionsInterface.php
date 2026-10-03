<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyOptions;

interface CreatePasskeyOptionsInterface
{
    public function process(CreatePasskeyOptionsInputPort $input, CreatePasskeyOptionsOutputPort $output): void;
}
