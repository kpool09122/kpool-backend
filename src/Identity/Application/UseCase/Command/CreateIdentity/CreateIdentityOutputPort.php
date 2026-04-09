<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateIdentity;

use Source\Identity\Domain\Entity\Identity;

interface CreateIdentityOutputPort
{
    public function setIdentity(Identity $identity): void;
}
