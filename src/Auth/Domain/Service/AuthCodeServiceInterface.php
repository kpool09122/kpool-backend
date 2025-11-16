<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Service;

use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

interface AuthCodeServiceInterface
{
    public function generateCode(Email $email): AuthCode;

    public function send(AuthCodeSession $authCode): void;

    public function notifyConflict(Email $email): void;
}
