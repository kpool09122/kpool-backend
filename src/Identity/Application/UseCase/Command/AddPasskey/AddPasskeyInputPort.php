<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AddPasskeyInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function challengeKey(): ChallengeSessionKey;

    public function displayName(): PasskeyDisplayName;

    public function responseJson(): string;
}
