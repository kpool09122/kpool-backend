<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DeletePasskey;

interface DeletePasskeyInterface
{
    public function process(DeletePasskeyInputPort $input, DeletePasskeyOutputPort $output): void;
}
