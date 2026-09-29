<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

use Source\Identity\Domain\Entity\Identity;

interface AuthenticateWithPasskeyOutputPort
{
    public function setIdentity(Identity $identity): void;
}
