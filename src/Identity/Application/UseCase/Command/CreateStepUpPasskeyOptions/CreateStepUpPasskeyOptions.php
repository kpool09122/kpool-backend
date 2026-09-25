<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateStepUpPasskeyOptions;

use DateTimeImmutable;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Application\Service\WebAuthn\StepUpAuthenticationChallenge;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\PasskeyRecoveryRequiredException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class CreateStepUpPasskeyOptions implements CreateStepUpPasskeyOptionsInterface
{
    private const int CHALLENGE_TTL_SECONDS = 300;

    public function __construct(
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private WebAuthnChallengeGeneratorInterface $challengeGenerator,
        private WebAuthnServiceInterface $webAuthnService,
        private ChallengeSessionStorageServiceInterface $challengeStorage,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function process(CreateStepUpPasskeyOptionsInputPort $input, CreateStepUpPasskeyOptionsOutputPort $output): void
    {
        $credentials = $this->credentialRepository->findByIdentityIdentifier($input->identityIdentifier());
        if ($credentials === []) {
            throw new PasskeyRecoveryRequiredException('No passkey is available for step-up authentication.');
        }
        $key = new ChallengeSessionKey($this->uuidGenerator->generate());
        $challenge = $this->challengeGenerator->generate();
        $options = $this->webAuthnService->createAuthenticationOptions(new AuthenticationOptionsInput(
            $challenge,
            array_map(static fn (PasskeyCredential $credential) => $credential->credentialId(), $credentials),
        ));
        $this->challengeStorage->storeStepUpAuthentication(new StepUpAuthenticationChallenge(
            $key,
            $challenge,
            $options,
            new DateTimeImmutable('+'.self::CHALLENGE_TTL_SECONDS.' seconds'),
            $input->identityIdentifier(),
        ));
        $output->setOptions($key, $options);
    }
}
