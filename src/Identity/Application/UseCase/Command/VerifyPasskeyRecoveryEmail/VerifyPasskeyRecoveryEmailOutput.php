<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use LogicException;
use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;

class VerifyPasskeyRecoveryEmailOutput implements VerifyPasskeyRecoveryEmailOutputPort
{
    private ?PasskeyRecoveryKey $key = null;

    public function setRecoveryKey(PasskeyRecoveryKey $key): void
    {
        $this->key = $key;
    }

    /** @return array{recoveryKey: string} */
    public function toArray(): array
    {
        if ($this->key === null) {
            throw new LogicException('Recovery key is not set.');
        }

        return ['recoveryKey' => (string) $this->key];
    }
}
