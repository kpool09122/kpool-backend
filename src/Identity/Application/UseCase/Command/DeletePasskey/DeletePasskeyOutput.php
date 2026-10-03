<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DeletePasskey;

class DeletePasskeyOutput implements DeletePasskeyOutputPort
{
    public function toArray(): array
    {
        return [];
    }
}
