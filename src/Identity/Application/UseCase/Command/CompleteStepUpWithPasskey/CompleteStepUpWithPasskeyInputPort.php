<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CompleteStepUpWithPasskeyInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function challengeKey(): ChallengeSessionKey;

    public function credentialId(): WebAuthnCredentialId;

    public function responseJson(): string;
}
