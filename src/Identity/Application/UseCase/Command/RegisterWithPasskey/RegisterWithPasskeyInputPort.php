<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterWithPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\Language;

interface RegisterWithPasskeyInputPort
{
    public function challengeKey(): ChallengeSessionKey;

    public function identityName(): IdentityName;

    public function language(): Language;

    public function displayName(): PasskeyDisplayName;

    public function responseJson(): string;

    public function base64EncodedImage(): ?string;
}
