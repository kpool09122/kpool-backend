<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CompleteStepUpWithPasskeyInput implements CompleteStepUpWithPasskeyInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier, private ChallengeSessionKey $challengeKey, private WebAuthnCredentialId $credentialId, private string $responseJson)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
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
