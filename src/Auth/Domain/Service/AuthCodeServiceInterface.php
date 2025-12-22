<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Service;

use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface AuthCodeServiceInterface
{
    public function generateCode(Email $email): AuthCode;

    public function send(Email $email, Language $language, AuthCodeSession $authCodeSession): void;

    public function notifyConflict(Email $email, Language $language): void;
}
