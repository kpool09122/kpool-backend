<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

interface CreatePasskeyRecoveryOptionsOutputPort
{
    public function setOptions(ChallengeSessionKey $key, WebAuthnOptions $options): void;
}
