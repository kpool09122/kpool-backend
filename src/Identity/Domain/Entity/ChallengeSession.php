<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use Source\Identity\Domain\ValueObject\ChallengePurpose;
use Source\Identity\Domain\ValueObject\ChallengeSessionIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyRegistrationContext;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ChallengeSession
{
    public function __construct(
        private ChallengeSessionIdentifier $identifier,
        private WebAuthnChallenge $challenge,
        private ChallengePurpose $purpose,
        private string $options,
        private DateTimeImmutable $expiresAt,
        private ?IdentityIdentifier $identityIdentifier = null,
        private ?PasskeyRegistrationContext $registrationContext = null,
    ) {
        try {
            json_decode($options, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('WebAuthn options must be valid JSON.', previous: $exception);
        }
        if ($purpose === ChallengePurpose::ADDITION && $identityIdentifier === null) {
            throw new InvalidArgumentException('Addition challenge must be associated with an identity.');
        }
        if ($purpose === ChallengePurpose::REGISTRATION && $registrationContext === null) {
            throw new InvalidArgumentException('Registration challenge must include registration context.');
        }
        if ($purpose !== ChallengePurpose::REGISTRATION && $registrationContext !== null) {
            throw new InvalidArgumentException('Only registration challenges may include registration context.');
        }
    }

    public function identifier(): ChallengeSessionIdentifier
    {
        return $this->identifier;
    }

    public function challenge(): WebAuthnChallenge
    {
        return $this->challenge;
    }

    public function purpose(): ChallengePurpose
    {
        return $this->purpose;
    }

    public function options(): string
    {
        return $this->options;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function identityIdentifier(): ?IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function registrationContext(): ?PasskeyRegistrationContext
    {
        return $this->registrationContext;
    }
}
