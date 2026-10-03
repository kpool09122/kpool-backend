<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

interface CreateStepUpPasskeyOptionsOutputPort
{
    public function setOptions(ChallengeSessionKey $challengeKey, WebAuthnOptions $options): void;
}
