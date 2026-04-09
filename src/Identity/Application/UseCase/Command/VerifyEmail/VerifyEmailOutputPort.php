<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use Source\Identity\Domain\Entity\AuthCodeSession;

interface VerifyEmailOutputPort
{
    public function setSession(AuthCodeSession $session): void;
}
