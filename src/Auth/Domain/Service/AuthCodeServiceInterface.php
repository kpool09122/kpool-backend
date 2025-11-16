<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Service;

use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeServiceInterface
{
    public function generateSession(Email $email): AuthCodeSession;

    public function send(AuthCodeSession $authCode): void;

    public function notifyConflict(Email $email): void;
}
