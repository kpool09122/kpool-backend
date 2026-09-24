<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AddPasskey;

use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\RegistrationVerificationInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\PasskeyCredentialAlreadyExistsException;
use Source\Identity\Domain\Exception\PasskeyUserNotFoundException;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;

readonly class AddPasskey implements AddPasskeyInterface
{
    public function __construct(
        private ChallengeSessionStorageServiceInterface $challengeSessionStorage,
        private PasskeyUserRepositoryInterface $passkeyUserRepository,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private PasskeyCredentialFactoryInterface $passkeyCredentialFactory,
        private WebAuthnServiceInterface $webAuthnService,
    ) {
    }

    public function process(AddPasskeyInputPort $input, AddPasskeyOutputPort $output): void
    {
        $challenge = $this->challengeSessionStorage->consumeAddition(
            $input->challengeKey(),
            $input->identityIdentifier(),
        );
        $passkeyUser = $this->passkeyUserRepository->findByIdentityIdentifier($input->identityIdentifier());
        if ($passkeyUser === null) {
            throw new PasskeyUserNotFoundException();
        }

        $verified = $this->webAuthnService->verifyRegistration(new RegistrationVerificationInput(
            $input->responseJson(),
            $challenge->options->json(),
        ));
        if ($this->passkeyCredentialRepository->findByCredentialId($verified->credentialId) !== null) {
            throw new PasskeyCredentialAlreadyExistsException();
        }

        $this->passkeyCredentialRepository->save($this->passkeyCredentialFactory->create(
            $passkeyUser->identifier(),
            $verified->credentialId,
            $verified->credentialSource,
            $verified->signCount,
            $verified->backupEligible,
            $verified->backupState,
            $verified->transports,
            $input->displayName(),
        ));
    }
}
