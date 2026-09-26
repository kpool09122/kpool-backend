<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskey;

interface AddPasskeyOutputPort
{
    /** @return array<string, never> */
    public function toArray(): array;
}
