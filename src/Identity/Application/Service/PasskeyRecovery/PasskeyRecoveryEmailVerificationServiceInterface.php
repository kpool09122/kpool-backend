<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

interface PasskeyRecoveryEmailVerificationServiceInterface
{
    public function send(Email $email, ?IdentityIdentifier $identityIdentifier, Language $language): EmailSendingStatus;

    public function verify(Email $email, AuthCode $code): IdentityIdentifier;
}
