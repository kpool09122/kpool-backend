<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

class DeleteAccountDataOutput implements DeleteAccountDataOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
