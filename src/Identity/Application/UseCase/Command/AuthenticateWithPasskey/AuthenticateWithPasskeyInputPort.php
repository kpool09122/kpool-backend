<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

interface AuthenticateWithPasskeyInputPort
{
    public function challengeKey(): ChallengeSessionKey;

    public function credentialId(): WebAuthnCredentialId;

    public function responseJson(): string;
}
