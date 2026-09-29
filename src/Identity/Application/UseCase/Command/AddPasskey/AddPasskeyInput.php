<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AddPasskeyInput implements AddPasskeyInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private ChallengeSessionKey $challengeKey,
        private PasskeyDisplayName $displayName,
        private string $responseJson,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function challengeKey(): ChallengeSessionKey
    {
        return $this->challengeKey;
    }

    public function displayName(): PasskeyDisplayName
    {
        return $this->displayName;
    }

    public function responseJson(): string
    {
        return $this->responseJson;
    }
}
