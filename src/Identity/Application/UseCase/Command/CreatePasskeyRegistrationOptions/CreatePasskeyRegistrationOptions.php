<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreatePasskeyRegistrationOptions;

use DateTimeImmutable;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\SignupInvitationValidatorInterface;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Entity\ChallengeSession;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\UnauthorizedEmailException;
use Source\Identity\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\WebAuthnChallengeGeneratorInterface;
use Source\Identity\Domain\ValueObject\ChallengePurpose;
use Source\Identity\Domain\ValueObject\ChallengeSessionIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyRegistrationContext;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CreatePasskeyRegistrationOptions implements CreatePasskeyRegistrationOptionsInterface
{
    private const int CHALLENGE_TTL_SECONDS = 300;

    public function __construct(
        private AuthCodeSessionRepositoryInterface $authCodeSessionRepository,
        private IdentityRepositoryInterface $identityRepository,
        private SignupInvitationValidatorInterface $signupInvitationValidator,
        private WebAuthnChallengeGeneratorInterface $challengeGenerator,
        private WebAuthnServiceInterface $webAuthnService,
        private ChallengeSessionStorageServiceInterface $challengeSessionStorage,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function process(
        CreatePasskeyRegistrationOptionsInputPort $input,
        CreatePasskeyRegistrationOptionsOutputPort $output,
    ): void {
        if ($this->identityRepository->findByEmail($input->email()) !== null) {
            throw new AlreadyUserExistsException();
        }

        $oneTimeToken = $input->signupSession()->oneTimeToken();
        if ($oneTimeToken !== null) {
            $this->signupInvitationValidator->validate($oneTimeToken, $input->email());
        } else {
            $session = $this->authCodeSessionRepository->findByEmail($input->email());
            if ($session === null) {
                throw new AuthCodeSessionNotFoundException();
            }
            $session->checkNotExpired(new DateTimeImmutable());
            if ($session->verifiedAt() === null) {
                throw new UnauthorizedEmailException('The email address has not been verified.');
            }
        }

        $challengeIdentifier = new ChallengeSessionIdentifier($this->uuidGenerator->generate());
        $identityIdentifier = new IdentityIdentifier($this->uuidGenerator->generate());
        $challenge = $this->challengeGenerator->generate();
        $options = $this->webAuthnService->createRegistrationOptions(new RegistrationOptionsInput(
            $challenge,
            (string) $identityIdentifier,
            (string) $input->email(),
            (string) $input->email(),
            [],
        ));
        $this->challengeSessionStorage->store(new ChallengeSession(
            $challengeIdentifier,
            $challenge,
            ChallengePurpose::REGISTRATION,
            $options->json(),
            new DateTimeImmutable('+' . self::CHALLENGE_TTL_SECONDS . ' seconds'),
            registrationContext: new PasskeyRegistrationContext(
                $input->email(),
                $identityIdentifier,
                $input->signupSession(),
            ),
        ));

        $output->setOptions($challengeIdentifier, $options);
    }
}
