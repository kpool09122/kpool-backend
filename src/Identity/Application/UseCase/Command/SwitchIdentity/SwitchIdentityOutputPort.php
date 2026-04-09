<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SwitchIdentity;

use Source\Identity\Domain\Entity\Identity;

interface SwitchIdentityOutputPort
{
    public function setIdentity(Identity $identity): void;
}
