<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RevokeOperations;

class RevokeOperationsOutput implements RevokeOperationsOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
