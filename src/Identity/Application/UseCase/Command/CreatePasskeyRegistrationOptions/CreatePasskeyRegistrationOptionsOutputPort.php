<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionIdentifier;

interface CreatePasskeyRegistrationOptionsOutputPort
{
    public function setOptions(ChallengeSessionIdentifier $challengeIdentifier, WebAuthnOptions $options): void;
}
