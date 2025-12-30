<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\AuthCodeMail;
use Application\Mail\ConflictNotificationMail;
use Illuminate\Support\Facades\Mail;
use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;

readonly class AuthCodeService implements AuthCodeServiceInterface
{
    public function generateCode(Email $email): AuthCode
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return new AuthCode($code);
    }

    public function send(Email $email, Language $language, AuthCodeSession $authCodeSession): void
    {
        Mail::to((string) $email)->send(
            new AuthCodeMail($language,  $authCodeSession)
        );
    }

    public function notifyConflict(Email $email, Language $language): void
    {
        Mail::to((string) $email)->send(
            new ConflictNotificationMail($language)
        );
    }
}
