<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RevokeOperations;

use Source\Shared\Domain\ValueObject\Email;

readonly class RevokeOperationsInput implements RevokeOperationsInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}
