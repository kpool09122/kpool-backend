<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRecoveryOptions;

use DateTimeImmutable;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\RecoveryRegistrationChallenge;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\ChallengeSessionKey;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class CreatePasskeyRecoveryOptions implements CreatePasskeyRecoveryOptionsInterface
{
    public function __construct(private PasskeyRecoverySessionStorageServiceInterface $sessions, private IdentityRepositoryInterface $identityRepository, private PasskeyUserRepositoryInterface $passkeyUserRepository, private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository, private WebAuthnChallengeGeneratorInterface $challenges, private WebAuthnServiceInterface $webAuthn, private ChallengeSessionStorageServiceInterface $challengeStorage, private UuidGeneratorInterface $uuid)
    {
    }

    public function process(CreatePasskeyRecoveryOptionsInputPort $input, CreatePasskeyRecoveryOptionsOutputPort $output): void
    {
        $session = $this->sessions->requireValid($input->recoveryKey());
        $identity = $this->identityRepository->findById($session->identityIdentifier) ?? throw new IdentityNotFoundException();
        $user = $this->passkeyUserRepository->findByIdentityIdentifier($session->identityIdentifier) ?? throw new PasskeyUserNotFoundException();
        $challenge = $this->challenges->generate();
        $excluded = array_map(static fn ($credential) => $credential->credentialId(), $this->passkeyCredentialRepository->findByIdentityIdentifier($session->identityIdentifier));
        $options = $this->webAuthn->createRegistrationOptions(new RegistrationOptionsInput($challenge, (string)$user->identifier(), (string)$identity->email(), (string)$identity->email(), $excluded));
        $key = new ChallengeSessionKey($this->uuid->generate());
        $this->challengeStorage->storeRecoveryRegistration(new RecoveryRegistrationChallenge($key, $challenge, $options, new DateTimeImmutable('+300 seconds'), $session->identityIdentifier, $input->recoveryKey()));
        $output->setOptions($key, $options);
    }
}
