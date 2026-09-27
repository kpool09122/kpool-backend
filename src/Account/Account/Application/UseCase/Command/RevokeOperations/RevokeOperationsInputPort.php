<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RevokeOperations;

use Source\Shared\Domain\ValueObject\Email;

interface RevokeOperationsInputPort
{
    public function email(): Email;
}
