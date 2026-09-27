<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\GrantOperations;

use Source\Shared\Domain\ValueObject\Email;

interface GrantOperationsInputPort
{
    public function email(): Email;
}
