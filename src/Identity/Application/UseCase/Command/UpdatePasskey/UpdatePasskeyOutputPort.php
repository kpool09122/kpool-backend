<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdatePasskey;

interface UpdatePasskeyOutputPort
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
