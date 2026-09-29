<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

interface CreatePasskeyRegistrationOptionsOutputPort
{
    public function setOptions(ChallengeSessionKey $challengeKey, WebAuthnOptions $options): void;
}
