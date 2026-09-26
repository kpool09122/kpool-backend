<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyAuthenticationOptions;

use DateTimeImmutable;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class CreatePasskeyAuthenticationOptions implements CreatePasskeyAuthenticationOptionsInterface
{
    private const int CHALLENGE_TTL_SECONDS = 300;

    public function __construct(
        private WebAuthnChallengeGeneratorInterface $challengeGenerator,
        private WebAuthnServiceInterface $webAuthnService,
        private ChallengeSessionStorageServiceInterface $challengeSessionStorage,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function process(
        CreatePasskeyAuthenticationOptionsInputPort $input,
        CreatePasskeyAuthenticationOptionsOutputPort $output,
    ): void {
        $challengeKey = new ChallengeSessionKey($this->uuidGenerator->generate());
        $challenge = $this->challengeGenerator->generate();
        $options = $this->webAuthnService->createAuthenticationOptions(new AuthenticationOptionsInput($challenge));

        $this->challengeSessionStorage->storeAuthentication(new AuthenticationChallenge(
            $challengeKey,
            $challenge,
            $options,
            new DateTimeImmutable('+' . self::CHALLENGE_TTL_SECONDS . ' seconds'),
        ));

        $output->setOptions($challengeKey, $options);
    }
}
