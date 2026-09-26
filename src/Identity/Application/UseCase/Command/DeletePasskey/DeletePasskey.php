<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DeletePasskey;

use Source\Identity\Application\Exception\CannotDeleteLastAuthenticationMethodException;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\PasskeyCredentialNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;

readonly class DeletePasskey implements DeletePasskeyInterface
{
    public function __construct(
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private PasskeyUserRepositoryInterface $passkeyUserRepository,
        private IdentityRepositoryInterface $identityRepository,
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorage,
    ) {
    }

    public function process(DeletePasskeyInputPort $input, DeletePasskeyOutputPort $output): void
    {
        $credential = $this->passkeyCredentialRepository->findByIdentifier($input->passkeyIdentifier());
        if ($credential === null) {
            throw new PasskeyCredentialNotFoundException();
        }

        $passkeyUser = $this->passkeyUserRepository->findByIdentifier($credential->passkeyUserIdentifier());
        if (
            $passkeyUser === null
            || $passkeyUser->identityIdentifier() === null
            || (string) $passkeyUser->identityIdentifier() !== (string) $input->identityIdentifier()
        ) {
            throw new PasskeyCredentialNotFoundException();
        }

        $identity = $this->identityRepository->findById($input->identityIdentifier());
        if ($identity === null) {
            throw new IdentityNotFoundException();
        }

        $hasOtherPasskey = array_any(
            $this->passkeyCredentialRepository->findByIdentityIdentifier($input->identityIdentifier()),
            static fn (PasskeyCredential $registeredCredential): bool => (string) $registeredCredential->identifier()
                !== (string) $input->passkeyIdentifier(),
        );
        if (! $hasOtherPasskey && $identity->socialConnections() === []) {
            throw new CannotDeleteLastAuthenticationMethodException();
        }

        $this->stepUpAuthenticationStorage->requireValid(
            $input->identityIdentifier(),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
        );
        $this->passkeyCredentialRepository->delete($input->passkeyIdentifier());
    }
}
