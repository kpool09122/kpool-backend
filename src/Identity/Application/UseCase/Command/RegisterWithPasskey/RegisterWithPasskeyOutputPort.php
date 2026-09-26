<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterWithPasskey;

use Source\Identity\Domain\Entity\Identity;

interface RegisterWithPasskeyOutputPort
{
    public function setIdentity(Identity $identity, ?string $returnTo): void;
}
