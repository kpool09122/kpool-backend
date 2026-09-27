<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthState;

readonly class StartPasskeyRecoveryWithSocial implements StartPasskeyRecoveryWithSocialInterface
{
    public function __construct(
        private SocialOAuthServiceInterface $socialOAuthService,
        private OAuthStateGeneratorInterface $stateGenerator,
        private OAuthStateRepositoryInterface $oAuthStateRepository,
        private PasskeyRecoveryOAuthSessionStorageServiceInterface $passkeyRecoveryOAuthSessionStorageService,
    ) {
    }

    public function process(
        StartPasskeyRecoveryWithSocialInputPort $input,
        StartPasskeyRecoveryWithSocialOutputPort $output,
    ): void {
        $generatedState = $this->stateGenerator->generate();
        $state = new OAuthState('passkey-recovery-' . $generatedState, $generatedState->expiresAt());
        $this->oAuthStateRepository->store($state);
        $this->passkeyRecoveryOAuthSessionStorageService->store($state, new PasskeyRecoveryOAuthSession(
            $input->provider(),
            $state->expiresAt(),
        ));
        $output->setRedirectUrl($this->socialOAuthService->buildRedirectUrl($input->provider(), $state));
    }
}
