<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterWithPasskey;

use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Shared\Domain\ValueObject\Language;

readonly class RegisterWithPasskeyInput implements RegisterWithPasskeyInputPort
{
    public function __construct(
        private ChallengeSessionKey $challengeKey,
        private IdentityName $identityName,
        private Language $language,
        private PasskeyDisplayName $displayName,
        private string $responseJson,
        private ?string $base64EncodedImage,
    ) {
    }

    public function challengeKey(): ChallengeSessionKey
    {
        return $this->challengeKey;
    }

    public function identityName(): IdentityName
    {
        return $this->identityName;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function displayName(): PasskeyDisplayName
    {
        return $this->displayName;
    }

    public function responseJson(): string
    {
        return $this->responseJson;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }
}
