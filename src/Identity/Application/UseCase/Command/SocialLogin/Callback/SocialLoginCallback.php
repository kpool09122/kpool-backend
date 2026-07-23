<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Callback;

use Psr\Log\LoggerInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Shared\Application\Exception\InvalidRemoteImageException;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;

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
        private EventDispatcherInterface           $eventDispatcher,
        private ImageServiceInterface              $imageService,
        private LoggerInterface                    $logger,
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
        $signupSession = $this->signupSessionRepository->find($input->state());
        $redirectUrl = $signupSession?->returnTo() ?? self::DEFAULT_REDIRECT_URL;

        $profile = $this->socialOAuthClient->fetchProfile($input->provider(), $input->code());
        $connection = new SocialConnection($input->provider(), $profile->providerUserId());
        $identity = $this->identityRepository->findBySocialConnection($connection->provider(), $connection->providerUserId());

        if ($identity !== null) {
            $this->authService->login($identity);
            if ($signupSession !== null) {
                $this->signupSessionRepository->delete($input->state());
            }
            $output->setRedirectUrl($redirectUrl);

            return;
        }

        $existingIdentity = $this->identityRepository->findByEmail($profile->email());
        if ($existingIdentity !== null) {
            if (! $existingIdentity->hasSocialConnection($connection)) {
                $existingIdentity->addSocialConnection($connection);
                $this->identityRepository->save($existingIdentity);
            }

            $this->authService->login($existingIdentity);
            if ($signupSession !== null) {
                $this->signupSessionRepository->delete($input->state());
            }
            $output->setRedirectUrl($redirectUrl);

            return;
        }

        $accountType = $signupSession?->accountType() ?? AccountType::INDIVIDUAL;

        $newIdentity = $this->identityFactory->createFromSocialProfile($profile);
        if ($profile->avatarUrl() !== null) {
            try {
                $uploadResult = $this->imageService->importFromUrl($profile->avatarUrl());
                $newIdentity->setProfileImage($uploadResult->path);
            } catch (InvalidRemoteImageException $e) {
                $this->logger->warning('Failed to import social profile image.', [
                    'provider' => $input->provider()->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $newIdentity->hasSocialConnection($connection)) {
            $newIdentity->addSocialConnection($connection);
        }
        $this->identityRepository->save($newIdentity);

        if ($invitationToken = $signupSession?->invitationToken()) {
            $this->eventDispatcher->dispatch(new IdentityCreatedViaInvitation(
                identityIdentifier: $newIdentity->identityIdentifier(),
                invitationToken: $invitationToken,
            ));
        } else {
            $this->eventDispatcher->dispatch(new IdentityCreated(
                identityIdentifier: $newIdentity->identityIdentifier(),
                email: $profile->email(),
                accountType: $accountType,
                name: $profile->name(),
            ));
        }

        if ($signupSession !== null) {
            $this->signupSessionRepository->delete($input->state());
        }

        $this->authService->login($newIdentity);
        $output->setRedirectUrl($redirectUrl);
    }
}
