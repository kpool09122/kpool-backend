<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use Source\Identity\Domain\ValueObject\PasskeyRecoveryKey;

interface CreatePasskeyRecoveryOptionsInputPort
{
    public function recoveryKey(): PasskeyRecoveryKey;
}
