<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RecoverPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;

readonly class RecoverPasskeyInput implements RecoverPasskeyInputPort
{
    public function __construct(private PasskeyRecoveryKey $recoveryKey, private ChallengeSessionKey $challengeKey, private PasskeyDisplayName $displayName, private string $responseJson)
    {
    }

    public function recoveryKey(): PasskeyRecoveryKey
    {
        return $this->recoveryKey;
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
