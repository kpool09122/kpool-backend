<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthState;

readonly class StartPasskeyRecoveryWithSocial implements StartPasskeyRecoveryWithSocialInterface
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private SocialOAuthServiceInterface $socialOAuthService,
        private OAuthStateGeneratorInterface $stateGenerator,
        private OAuthStateRepositoryInterface $stateRepository,
        private PasskeyRecoveryOAuthSessionStorageServiceInterface $sessionStorage,
    ) {
    }

    public function process(
        StartPasskeyRecoveryWithSocialInputPort $input,
        StartPasskeyRecoveryWithSocialOutputPort $output,
    ): void {
        $identity = $this->identityRepository->findById($input->identityIdentifier());
        if ($identity === null || ! array_any(
            $identity->socialConnections(),
            static fn ($connection): bool => $connection->provider() === $input->provider(),
        )) {
            throw new PasskeyRecoveryVerificationFailedException('The selected provider is not linked to this identity.');
        }

        if ($this->passkeyCredentialRepository->findByIdentityIdentifier($input->identityIdentifier()) === []) {
            throw new PasskeyRecoveryVerificationFailedException('No passkey is available to recover.');
        }

        $generatedState = $this->stateGenerator->generate();
        $state = new OAuthState('passkey-recovery-' . $generatedState, $generatedState->expiresAt());
        $this->stateRepository->store($state);
        $this->sessionStorage->store($state, new PasskeyRecoveryOAuthSession(
            $input->identityIdentifier(),
            $input->provider(),
            $state->expiresAt(),
        ));
        $output->setRedirectUrl($this->socialOAuthService->buildRedirectUrl($input->provider(), $state));
    }
}
