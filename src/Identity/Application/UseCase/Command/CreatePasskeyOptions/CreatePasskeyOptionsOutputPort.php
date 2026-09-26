<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

interface CreatePasskeyOptionsOutputPort
{
    public function setOptions(ChallengeSessionKey $challengeKey, WebAuthnOptions $options): void;
}
