<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use Source\Identity\Domain\Entity\Identity;

interface LoginOutputPort
{
    public function setIdentity(Identity $identity): void;
}
