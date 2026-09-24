<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskeyOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

interface AddPasskeyOptionsOutputPort
{
    public function setOptions(ChallengeSessionKey $challengeKey, WebAuthnOptions $options): void;
}
