<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyPasskeyRecoveryEmail;

use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;

interface VerifyPasskeyRecoveryEmailOutputPort
{
    public function setRecoveryKey(PasskeyRecoveryKey $key): void;
}
