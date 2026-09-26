<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RecoverPasskey;

interface RecoverPasskeyInterface
{
    public function process(RecoverPasskeyInputPort $input): void;
}
