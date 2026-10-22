<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Application\Service\OAuthStateGeneratorInterface;
use Source\Identity\Application\Service\OAuthStateSession;
use Source\Identity\Application\Service\OAuthStateStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\OAuthState;

readonly class StartPasskeyRecoveryWithSocial implements StartPasskeyRecoveryWithSocialInterface
{
    public function __construct(
        private SocialOAuthServiceInterface $socialOAuthService,
        private OAuthStateGeneratorInterface $oauthStateGenerator,
        private OAuthStateStorageServiceInterface $oAuthStateStorageService,
        private PasskeyRecoveryOAuthSessionStorageServiceInterface $passkeyRecoveryOAuthSessionStorageService,
    ) {
    }

    public function process(
        StartPasskeyRecoveryWithSocialInputPort $input,
        StartPasskeyRecoveryWithSocialOutputPort $output,
    ): void {
        $generatedSession = $this->oauthStateGenerator->generate();
        $state = new OAuthState('passkey-recovery-' . $generatedSession->state);
        $this->oAuthStateStorageService->store(new OAuthStateSession($state, $generatedSession->expiresAt));
        $this->passkeyRecoveryOAuthSessionStorageService->store($state, new PasskeyRecoveryOAuthSession(
            $input->provider(),
            $generatedSession->expiresAt,
        ));
        $output->setRedirectUrl($this->socialOAuthService->buildRedirectUrl($input->provider(), $state));
    }
}
