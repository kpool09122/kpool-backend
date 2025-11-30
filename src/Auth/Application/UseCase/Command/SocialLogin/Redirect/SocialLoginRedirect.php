<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Redirect;

use Source\Auth\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Auth\Domain\Service\OAuthStateGeneratorInterface;
use Source\Auth\Domain\Service\SocialOAuthClientInterface;

readonly class SocialLoginRedirect implements SocialLoginRedirectInterface
{
    public function __construct(
        private SocialOAuthClientInterface $socialOAuthClient,
        private OAuthStateGeneratorInterface $oauthStateGenerator,
        private OAuthStateRepositoryInterface $oauthStateRepository,
    ) {
    }

    public function process(SocialLoginRedirectInputPort $input, SocialLoginRedirectOutputPort $output): void
    {
        $state = $this->oauthStateGenerator->generate();
        $this->oauthStateRepository->store($state);
        $redirectUrl = $this->socialOAuthClient->buildRedirectUrl($input->provider(), $state);

        $output->setRedirectUrl($redirectUrl);
    }
}
