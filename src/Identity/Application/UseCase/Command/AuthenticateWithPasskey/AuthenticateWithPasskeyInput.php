<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

readonly class AuthenticateWithPasskeyInput implements AuthenticateWithPasskeyInputPort
{
    public function __construct(
        private ChallengeSessionKey $challengeKey,
        private WebAuthnCredentialId $credentialId,
        private string $responseJson,
    ) {
    }

    public function challengeKey(): ChallengeSessionKey
    {
        return $this->challengeKey;
    }

    public function credentialId(): WebAuthnCredentialId
    {
        return $this->credentialId;
    }

    public function responseJson(): string
    {
        return $this->responseJson;
    }
}
