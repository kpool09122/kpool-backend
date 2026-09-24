<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskeyOptions;

interface AddPasskeyOptionsInterface
{
    public function process(AddPasskeyOptionsInputPort $input, AddPasskeyOptionsOutputPort $output): void;
}
