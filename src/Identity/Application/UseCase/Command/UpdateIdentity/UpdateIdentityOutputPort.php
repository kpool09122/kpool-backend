<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateIdentity;

use Source\Identity\Domain\Entity\Identity;

interface UpdateIdentityOutputPort
{
    public function setIdentity(Identity $identity): void;
}
