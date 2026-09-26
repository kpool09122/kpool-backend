<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;

readonly class CreatePasskeyRecoveryOptionsInput implements CreatePasskeyRecoveryOptionsInputPort
{
    public function __construct(private PasskeyRecoveryKey $recoveryKey)
    {
    }

    public function recoveryKey(): PasskeyRecoveryKey
    {
        return $this->recoveryKey;
    }
}
