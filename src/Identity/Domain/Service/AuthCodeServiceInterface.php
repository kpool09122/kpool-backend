<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

interface AuthCodeServiceInterface
{
    public function generateCode(Email $email): AuthCode;

    public function send(Email $email, Language $language, AuthCodeSession $authCodeSession): void;

    public function notifyConflict(Email $email, Language $language): void;
}
