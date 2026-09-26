<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use Source\Identity\Application\Service\StepUpOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\StepUpSocialAuthenticationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Domain\ValueObject\StepUpOAuthSession;

readonly class StartStepUpWithSocial implements StartStepUpWithSocialInterface
{
    private const string RETURN_TO = '/settings/passkeys?stepUp=complete';

    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private SocialOAuthServiceInterface $socialOAuthService,
        private OAuthStateGeneratorInterface $stateGenerator,
        private OAuthStateRepositoryInterface $stateRepository,
        private StepUpOAuthSessionStorageServiceInterface $sessionStorage,
    ) {
    }

    public function process(StartStepUpWithSocialInputPort $input, StartStepUpWithSocialOutputPort $output): void
    {
        $identity = $this->identityRepository->findById($input->identityIdentifier());
        if ($identity === null || ! array_any($identity->socialConnections(), static fn ($connection) => $connection->provider() === $input->provider())) {
            throw new StepUpSocialAuthenticationFailedException('The selected provider is not linked to this identity.');
        }
        if ($this->passkeyCredentialRepository->findByIdentityIdentifier($input->identityIdentifier()) !== []) {
            throw new StepUpSocialAuthenticationFailedException('An existing passkey must be used for step-up authentication.');
        }
        $generatedState = $this->stateGenerator->generate();
        $state = new OAuthState('step-up-' . $generatedState, $generatedState->expiresAt());
        $this->stateRepository->store($state);
        $this->sessionStorage->store($state, new StepUpOAuthSession($input->identityIdentifier(), $input->provider(), StepUpAuthenticationScope::PASSKEY_MANAGE, $state->expiresAt(), self::RETURN_TO));
        $output->setRedirectUrl($this->socialOAuthService->buildRedirectUrl($input->provider(), $state));
    }
}
