<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\AuthenticateWithPasskey;

use DateTimeImmutable;
use Source\Identity\Application\Service\ChallengeSessionStorageServiceInterface;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Application\Service\WebAuthnServiceInterface;
use Source\Identity\Domain\Exception\PasskeyAuthenticationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;

readonly class AuthenticateWithPasskey implements AuthenticateWithPasskeyInterface
{
    public function __construct(
        private ChallengeSessionStorageServiceInterface $challengeSessionStorageService,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private PasskeyUserRepositoryInterface $passkeyUserRepository,
        private IdentityRepositoryInterface $identityRepository,
        private WebAuthnServiceInterface $webAuthnService,
        private AuthServiceInterface $authService,
    ) {
    }

    public function process(
        AuthenticateWithPasskeyInputPort $input,
        AuthenticateWithPasskeyOutputPort $output,
    ): void {
        $challenge = $this->challengeSessionStorageService->consumeAuthentication($input->challengeKey());
        $credential = $this->passkeyCredentialRepository->findByCredentialId($input->credentialId());
        if ($credential === null) {
            throw new PasskeyAuthenticationFailedException();
        }

        $passkeyUser = $this->passkeyUserRepository->findByIdentifier($credential->passkeyUserIdentifier());
        $identityIdentifier = $passkeyUser?->identityIdentifier();
        if ($identityIdentifier === null) {
            throw new PasskeyAuthenticationFailedException();
        }

        $identity = $this->identityRepository->findById($identityIdentifier);
        if ($identity === null) {
            throw new PasskeyAuthenticationFailedException();
        }

        $verified = $this->webAuthnService->verifyAuthentication(new AuthenticationVerificationInput(
            $input->responseJson(),
            $challenge->options->json(),
            $credential->credentialSource(),
            (string) $passkeyUser->identifier(),
        ));
        $credential->recordAuthentication(
            $verified->credentialSource,
            $verified->signCount,
            $verified->backupEligible,
            $verified->backupState,
            new DateTimeImmutable(),
        );
        $this->passkeyCredentialRepository->save($credential);
        $this->authService->login($identity);
        $output->setIdentity($identity);
    }
}
