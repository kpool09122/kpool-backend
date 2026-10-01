<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\GrantOperations;

use Source\Shared\Domain\ValueObject\Email;

readonly class GrantOperationsInput implements GrantOperationsInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}
