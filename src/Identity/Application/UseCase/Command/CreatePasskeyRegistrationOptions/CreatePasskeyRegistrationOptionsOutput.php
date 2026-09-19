<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionIdentifier;

class CreatePasskeyRegistrationOptionsOutput implements CreatePasskeyRegistrationOptionsOutputPort
{
    private ?ChallengeSessionIdentifier $challengeIdentifier = null;
    private ?WebAuthnOptions $options = null;

    public function setOptions(ChallengeSessionIdentifier $challengeIdentifier, WebAuthnOptions $options): void
    {
        $this->challengeIdentifier = $challengeIdentifier;
        $this->options = $options;
    }

    /** @return array{challengeIdentifier?: string, options?: array<string, mixed>} */
    public function toArray(): array
    {
        if ($this->challengeIdentifier === null || $this->options === null) {
            return [];
        }

        /** @var array<string, mixed> $options */
        $options = json_decode($this->options->json(), true, flags: JSON_THROW_ON_ERROR);

        return [
            'challengeIdentifier' => (string) $this->challengeIdentifier,
            'options' => $options,
        ];
    }
}
