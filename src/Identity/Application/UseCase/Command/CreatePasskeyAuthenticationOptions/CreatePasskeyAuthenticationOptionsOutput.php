<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions;

use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class CreatePasskeyAuthenticationOptionsOutput implements CreatePasskeyAuthenticationOptionsOutputPort
{
    private ?ChallengeSessionKey $challengeKey = null;
    private ?WebAuthnOptions $options = null;

    public function setOptions(ChallengeSessionKey $challengeKey, WebAuthnOptions $options): void
    {
        $this->challengeKey = $challengeKey;
        $this->options = $options;
    }

    /** @return array{challengeKey?: string, options?: array<string, mixed>} */
    public function toArray(): array
    {
        if ($this->challengeKey === null || $this->options === null) {
            return [];
        }

        /** @var array<string, mixed> $options */
        $options = json_decode($this->options->json(), true, flags: JSON_THROW_ON_ERROR);

        return [
            'challengeKey' => (string) $this->challengeKey,
            'options' => $options,
        ];
    }
}
