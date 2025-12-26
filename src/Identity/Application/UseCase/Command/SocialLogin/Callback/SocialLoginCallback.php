<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Callback;

use Source\Identity\Application\Service\AccountProvisioningServiceInterface;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthClientInterface;
use Source\Identity\Domain\ValueObject\SocialConnection;

readonly class SocialLoginCallback implements SocialLoginCallbackInterface
{
    private const DEFAULT_REDIRECT_URL = '/auth/callback';

    public function __construct(
        private OAuthStateRepositoryInterface $oauthStateRepository,
        private SocialOAuthClientInterface $socialOAuthClient,
        private IdentityRepositoryInterface $identityRepository,
        private IdentityFactoryInterface $identityFactory,
        private AccountProvisioningServiceInterface $accountProvisioningService,
        private AuthServiceInterface $authService,
    ) {
    }

    public function process(SocialLoginCallbackInputPort $input, SocialLoginCallbackOutputPort $output): void
    {
        $this->oauthStateRepository->consume($input->state());

        $profile = $this->socialOAuthClient->fetchProfile($input->provider(), $input->code());
        $connection = new SocialConnection($input->provider(), $profile->providerUserId());
        $identity = $this->identityRepository->findBySocialConnection($connection->provider(), $connection->providerUserId());

        if ($identity !== null) {
            $this->accountProvisioningService->provision($identity->identityIdentifier());
            $this->authService->login($identity);

            $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);

            return;
        }

        $existingIdentity = $this->identityRepository->findByEmail($profile->email());
        if ($existingIdentity !== null) {
            if (! $existingIdentity->hasSocialConnection($connection)) {
                $existingIdentity->addSocialConnection($connection);
                $this->identityRepository->save($existingIdentity);
            }

            $this->accountProvisioningService->provision($existingIdentity->identityIdentifier());
            $this->authService->login($existingIdentity);

            $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);

            return;
        }

        $newIdentity = $this->identityFactory->createFromSocialProfile($profile);
        if (! $newIdentity->hasSocialConnection($connection)) {
            $newIdentity->addSocialConnection($connection);
        }
        $this->identityRepository->save($newIdentity);
        $this->accountProvisioningService->provision($newIdentity->identityIdentifier());
        $this->authService->login($newIdentity);

        $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);

        return;
    }
}
