<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Callback;

use Source\Auth\Application\Service\AccountProvisioningServiceInterface;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\Service\AuthServiceInterface;
use Source\Auth\Domain\Service\SocialOAuthClientInterface;
use Source\Auth\Domain\ValueObject\SocialConnection;

readonly class SocialLoginCallback implements SocialLoginCallbackInterface
{
    private const DEFAULT_REDIRECT_URL = '/auth/callback';

    public function __construct(
        private OAuthStateRepositoryInterface $oauthStateRepository,
        private SocialOAuthClientInterface $socialOAuthClient,
        private UserRepositoryInterface $userRepository,
        private UserFactoryInterface $userFactory,
        private AccountProvisioningServiceInterface $accountProvisioningService,
        private AuthServiceInterface $authService,
    ) {
    }

    public function process(SocialLoginCallbackInputPort $input, SocialLoginCallbackOutputPort $output): void
    {
        $this->oauthStateRepository->consume($input->state());

        $profile = $this->socialOAuthClient->fetchProfile($input->provider(), $input->code());
        $connection = new SocialConnection($input->provider(), $profile->providerUserId());
        $user = $this->userRepository->findBySocialConnection($connection->provider(), $connection->providerUserId());

        if ($user !== null) {
            $this->accountProvisioningService->provision($user->userIdentifier());
            $this->authService->login($user);

            $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);

            return;
        }

        $existingUser = $this->userRepository->findByEmail($profile->email());
        if ($existingUser !== null) {
            if (! $existingUser->hasSocialConnection($connection)) {
                $existingUser->addSocialConnection($connection);
                $this->userRepository->save($existingUser);
            }

            $this->accountProvisioningService->provision($existingUser->userIdentifier());
            $this->authService->login($existingUser);

            $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);

            return;
        }

        $newUser = $this->userFactory->createFromSocialProfile($profile);
        if (! $newUser->hasSocialConnection($connection)) {
            $newUser->addSocialConnection($connection);
        }
        $this->userRepository->save($newUser);
        $this->accountProvisioningService->provision($newUser->userIdentifier());
        $this->authService->login($newUser);

        $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);

        return;
    }
}
