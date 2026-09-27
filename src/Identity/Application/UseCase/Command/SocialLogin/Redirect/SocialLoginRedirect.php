<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use Random\RandomException;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;

readonly class SocialLoginRedirect implements SocialLoginRedirectInterface
{
    public function __construct(
        private SocialOAuthServiceInterface      $socialOAuthService,
        private OAuthStateGeneratorInterface     $oauthStateGenerator,
        private OAuthStateRepositoryInterface    $oAuthStateRepository,
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
        $state = $this->oauthStateGenerator->generate();
        $this->oAuthStateRepository->store($state);

        if ($input->signupSession() !== null) {
            $this->signupSessionRepository->store($state, $input->signupSession());
        }

        $redirectUrl = $this->socialOAuthService->buildRedirectUrl($input->provider(), $state);

        $output->setRedirectUrl($redirectUrl);
    }
}
