<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterWithPasskey;

use DateTimeImmutable;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\SignupInvitationValidatorInterface;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyUserAlreadyLinkedException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;

readonly class RegisterWithPasskey implements RegisterWithPasskeyInterface
{
    public function __construct(
        private ChallengeSessionStorageServiceInterface $challengeSessionStorage,
        private SignupInvitationValidatorInterface $signupInvitationValidator,
        private PasskeyUserRepositoryInterface $passkeyUserRepository,
        private IdentityRepositoryInterface $identityRepository,
        private IdentityFactoryInterface $identityFactory,
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private PasskeyCredentialFactoryInterface $credentialFactory,
        private WebAuthnServiceInterface $webAuthnService,
        private ImageServiceInterface $imageService,
        private EventDispatcherInterface $eventDispatcher,
        private AuthServiceInterface $authService,
    ) {
    }

    public function process(RegisterWithPasskeyInputPort $input, RegisterWithPasskeyOutputPort $output): void
    {
        $challenge = $this->challengeSessionStorage->consumeRegistration($input->challengeKey());
        $oneTimeToken = $challenge->signupSession->oneTimeToken();
        if ($oneTimeToken !== null) {
            $this->signupInvitationValidator->validate($oneTimeToken, $challenge->email);
        }

        $passkeyUser = $this->passkeyUserRepository->findByIdentifier($challenge->passkeyUserIdentifier);
        if ($passkeyUser === null) {
            throw new PasskeyUserNotFoundException();
        }
        if ($passkeyUser->identityIdentifier() !== null) {
            throw new PasskeyUserAlreadyLinkedException();
        }
        if ($this->identityRepository->findByEmail($challenge->email) !== null) {
            throw new AlreadyUserExistsException();
        }

        $verified = $this->webAuthnService->verifyRegistration(new RegistrationVerificationInput(
            $input->responseJson(),
            $challenge->options->json(),
        ));
        if ($this->credentialRepository->findByCredentialId($verified->credentialId) !== null) {
            throw new PasskeyCredentialAlreadyExistsException();
        }

        $identity = $this->identityFactory->create(
            $input->identityName(),
            $challenge->email,
            $input->language(),
        );
        $identity->markEmailVerified(new DateTimeImmutable());
        if ($input->base64EncodedImage() !== null) {
            $identity->setProfileImage($this->imageService->upload($input->base64EncodedImage()));
        }

        $this->identityRepository->save($identity);
        $passkeyUser->linkToIdentity($identity->identityIdentifier());
        $this->passkeyUserRepository->save($passkeyUser);
        $this->credentialRepository->save($this->credentialFactory->create(
            $passkeyUser->identifier(),
            $verified->credentialId,
            $verified->credentialSource,
            $verified->signCount,
            $verified->backupEligible,
            $verified->backupState,
            $verified->transports,
            $input->displayName(),
        ));

        if ($oneTimeToken !== null) {
            $this->eventDispatcher->dispatch(new IdentityCreatedViaInvitation(
                $identity->identityIdentifier(),
                $oneTimeToken,
            ));
        } else {
            $this->eventDispatcher->dispatch(new IdentityCreated(
                $identity->identityIdentifier(),
                $challenge->email,
                $challenge->signupSession->accountType() ?? AccountType::INDIVIDUAL,
                (string) $input->identityName(),
            ));
        }

        $this->authService->login($identity);
        $output->setIdentity($identity, $challenge->signupSession->returnTo());
    }
}
