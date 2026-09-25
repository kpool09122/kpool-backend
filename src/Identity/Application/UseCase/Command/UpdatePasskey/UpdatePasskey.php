<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdatePasskey;

use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Exception\PasskeyCredentialNotFoundException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;

readonly class UpdatePasskey implements UpdatePasskeyInterface
{
    public function __construct(
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private PasskeyUserRepositoryInterface $passkeyUserRepository,
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorage,
    ) {
    }

    public function process(UpdatePasskeyInputPort $input, UpdatePasskeyOutputPort $output): void
    {
        $this->stepUpAuthenticationStorage->requireValid(
            $input->identityIdentifier(),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
        );

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

        $credential->rename($input->displayName());
        $this->passkeyCredentialRepository->save($credential);
    }
}
