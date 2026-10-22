<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use Random\RandomException;
use Source\Identity\Application\Service\OAuthStateGeneratorInterface;
use Source\Identity\Application\Service\OAuthStateStorageServiceInterface;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;

readonly class SocialLoginRedirect implements SocialLoginRedirectInterface
{
    public function __construct(
        private SocialOAuthServiceInterface      $socialOAuthService,
        private OAuthStateGeneratorInterface     $oauthStateGenerator,
        private OAuthStateStorageServiceInterface    $oAuthStateStorageService,
        private SignupSessionRepositoryInterface $signupSessionRepository,
    ) {
    }

    /**
     * @param SocialLoginRedirectInputPort $input
     * @param SocialLoginRedirectOutputPort $output
     * @return void
     * @throws RandomException
     * @throws InvalidOAuthStateException
     */
    public function process(SocialLoginRedirectInputPort $input, SocialLoginRedirectOutputPort $output): void
    {
        $session = $this->oauthStateGenerator->generate();
        $this->oAuthStateStorageService->store($session);
        $state = $session->state;

        if ($input->signupSession() !== null) {
            $this->signupSessionRepository->store($state, $input->signupSession(), $session->expiresAt);
        }

        $redirectUrl = $this->socialOAuthService->buildRedirectUrl($input->provider(), $state);

        $output->setRedirectUrl($redirectUrl);
    }
}
