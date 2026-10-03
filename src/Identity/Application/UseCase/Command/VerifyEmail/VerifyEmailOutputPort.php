<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use Source\Identity\Domain\ValueObject\AuthCodeSession;

interface VerifyEmailOutputPort
{
    public function setSession(AuthCodeSession $session): void;
}
