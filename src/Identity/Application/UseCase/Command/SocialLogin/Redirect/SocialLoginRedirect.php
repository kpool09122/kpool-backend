<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Service\OAuthStateGeneratorInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;

readonly class SocialLoginRedirect implements SocialLoginRedirectInterface
{
    public function __construct(
        private SocialOAuthServiceInterface   $socialOAuthClient,
        private OAuthStateGeneratorInterface  $oauthStateGenerator,
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
