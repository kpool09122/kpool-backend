<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Callback;

use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\SocialConnection;

readonly class SocialLoginCallback implements SocialLoginCallbackInterface
{
    private const string DEFAULT_REDIRECT_URL = '/auth/callback';

    public function __construct(
        private OAuthStateRepositoryInterface      $oauthStateRepository,
        private SocialOAuthServiceInterface        $socialOAuthClient,
        private IdentityRepositoryInterface        $identityRepository,
        private IdentityFactoryInterface           $identityFactory,
        private SignupSessionRepositoryInterface   $signupSessionRepository,
        private AuthServiceInterface               $authService,
    ) {
    }

    /**
     * @param SocialLoginCallbackInputPort $input
     * @param SocialLoginCallbackOutputPort $output
     * @return void
     * @throws InvalidOAuthStateException
     */
    public function process(SocialLoginCallbackInputPort $input, SocialLoginCallbackOutputPort $output): void
    {
        $this->oauthStateRepository->consume($input->state());

        $profile = $this->socialOAuthClient->fetchProfile($input->provider(), $input->code());
        $connection = new SocialConnection($input->provider(), $profile->providerUserId());
        $identity = $this->identityRepository->findBySocialConnection($connection->provider(), $connection->providerUserId());

        if ($identity !== null) {
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

            $this->authService->login($existingIdentity);
            $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);

            return;
        }

        $signupSession = $this->signupSessionRepository->find($input->state());
        $accountType = $signupSession?->accountType() ?? AccountType::INDIVIDUAL;

        $newIdentity = $this->identityFactory->createFromSocialProfile($profile);
        if (! $newIdentity->hasSocialConnection($connection)) {
            $newIdentity->addSocialConnection($connection);
        }
        $this->identityRepository->save($newIdentity);

        event(new IdentityCreated(
            identityIdentifier: $newIdentity->identityIdentifier(),
            email: $profile->email(),
            accountType: $accountType,
            name: $profile->name(),
        ));

        if ($signupSession !== null) {
            $this->signupSessionRepository->delete($input->state());
        }

        $this->authService->login($newIdentity);
        $output->setRedirectUrl(self::DEFAULT_REDIRECT_URL);
    }
}
