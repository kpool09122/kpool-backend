<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyOptions;

use DateTimeImmutable;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AdditionChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Factory\PasskeyUserFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class CreatePasskeyOptions implements CreatePasskeyOptionsInterface
{
    private const int CHALLENGE_TTL_SECONDS = 300;

    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private PasskeyUserFactoryInterface $passkeyUserFactory,
        private PasskeyUserRepositoryInterface $passkeyUserRepository,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private WebAuthnChallengeGeneratorInterface $challengeGenerator,
        private WebAuthnServiceInterface $webAuthnService,
        private ChallengeSessionStorageServiceInterface $challengeSessionStorage,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function process(CreatePasskeyOptionsInputPort $input, CreatePasskeyOptionsOutputPort $output): void
    {
        $identity = $this->identityRepository->findById($input->identityIdentifier());
        if ($identity === null) {
            throw new IdentityNotFoundException('Identity not found.');
        }

        $passkeyUser = $this->passkeyUserRepository->findByIdentityIdentifier($input->identityIdentifier());
        $isNewPasskeyUser = $passkeyUser === null;
        if ($passkeyUser === null) {
            $passkeyUser = $this->passkeyUserFactory->create($input->identityIdentifier());
        }

        $excludedCredentialIds = array_map(
            static fn (PasskeyCredential $credential) => $credential->credentialId(),
            $this->passkeyCredentialRepository->findByIdentityIdentifier($input->identityIdentifier()),
        );
        $challengeKey = new ChallengeSessionKey($this->uuidGenerator->generate());
        $challenge = $this->challengeGenerator->generate();
        $options = $this->webAuthnService->createRegistrationOptions(new RegistrationOptionsInput(
            $challenge,
            (string) $passkeyUser->identifier(),
            (string) $identity->email(),
            (string) $identity->identityName(),
            $excludedCredentialIds,
        ));

        if ($isNewPasskeyUser) {
            $this->passkeyUserRepository->save($passkeyUser);
        }

        $this->challengeSessionStorage->storeAddition(new AdditionChallenge(
            $challengeKey,
            $challenge,
            $options,
            new DateTimeImmutable('+' . self::CHALLENGE_TTL_SECONDS . ' seconds'),
            $input->identityIdentifier(),
        ));

        $output->setOptions($challengeKey, $options);
    }
}
