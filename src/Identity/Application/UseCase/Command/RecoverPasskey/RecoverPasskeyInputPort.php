<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RecoverPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;

interface RecoverPasskeyInputPort
{
    public function recoveryKey(): PasskeyRecoveryKey;

    public function challengeKey(): ChallengeSessionKey;

    public function displayName(): PasskeyDisplayName;

    public function responseJson(): string;
}
