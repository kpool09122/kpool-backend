<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use JsonException;
use LogicException;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;

class CreatePasskeyRecoveryOptionsOutput implements CreatePasskeyRecoveryOptionsOutputPort
{
    private ?ChallengeSessionKey $key = null;
    private ?WebAuthnOptions $options = null;

    public function setOptions(ChallengeSessionKey $key, WebAuthnOptions $options): void
    {
        $this->key = $key;
        $this->options = $options;
    }

    /** @return array<string,mixed> @throws JsonException */
    public function toArray(): array
    {
        if ($this->key === null || $this->options === null) {
            throw new LogicException('Options are not set.');
        }

        return ['challengeKey' => (string)$this->key,'options' => json_decode($this->options->json(), true, flags: JSON_THROW_ON_ERROR)];
    }
}
