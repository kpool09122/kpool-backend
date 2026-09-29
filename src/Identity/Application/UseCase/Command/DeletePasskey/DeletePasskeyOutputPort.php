<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DeletePasskey;

interface DeletePasskeyOutputPort
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
