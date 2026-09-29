<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Callback;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\Service\StepUpOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\Event\IdentityCreated;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\StepUpSocialAuthenticationFailedException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Repository\SignupSessionRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Shared\Application\Exception\InvalidRemoteImageException;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;

readonly class SocialLoginCallback implements SocialLoginCallbackInterface
{
    private const string DEFAULT_REDIRECT_URL = '/auth/callback';

    public function __construct(
        private OAuthStateRepositoryInterface      $oAuthStateRepository,
        private SocialOAuthServiceInterface        $socialOAuthService,
        private IdentityRepositoryInterface        $identityRepository,
        private IdentityFactoryInterface           $identityFactory,
        private SignupSessionRepositoryInterface   $signupSessionRepository,
        private AuthServiceInterface               $authService,
        private EventDispatcherInterface           $eventDispatcher,
        private StepUpOAuthSessionStorageServiceInterface $stepUpOAuthSessionStorageService,
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private ImageServiceInterface              $imageService,
        private LoggerInterface                    $logger,
        private SocialLinkingSessionStorageServiceInterface $socialLinkingSessionStorageService,
    ) {
    }

    /**
     * @param SocialLoginCallbackInputPort $input
     * @param SocialLoginCallbackOutputPort $output
     * @return void
     * @throws SocialLinkingSessionInvalidException
     * @throws InvalidOAuthStateException
     */
    public function process(SocialLoginCallbackInputPort $input, SocialLoginCallbackOutputPort $output): void
    {
        $this->oAuthStateRepository->consume($input->state());
        $isStepUp = str_starts_with((string) $input->state(), 'step-up-');
        $stepUpSession = $isStepUp
            ? $this->stepUpOAuthSessionStorageService->consume($input->state())
            : null;
        if ($isStepUp && $stepUpSession === null) {
            throw new StepUpSocialAuthenticationFailedException('Step-up OAuth session is missing or expired.');
        }
        if ($stepUpSession !== null) {
            if ($stepUpSession->provider !== $input->provider()) {
                throw new StepUpSocialAuthenticationFailedException();
            }

            $profile = $this->socialOAuthService->fetchProfile($input->provider(), $input->code());
            $connection = new SocialConnection($input->provider(), $profile->providerUserId());
            $identity = $this->identityRepository->findBySocialConnection($connection->provider(), $connection->providerUserId());
            if ($identity === null
                || (string) $identity->identityIdentifier() !== (string) $stepUpSession->identityIdentifier
                || $this->passkeyCredentialRepository->findByIdentityIdentifier($stepUpSession->identityIdentifier) !== []) {
                throw new StepUpSocialAuthenticationFailedException();
            }

            $now = new DateTimeImmutable();
            $this->stepUpAuthenticationStorageService->store(new StepUpAuthentication(
                $stepUpSession->identityIdentifier,
                StepUpAuthenticationMethod::SSO,
                $now,
                $stepUpSession->scope,
                $now->modify('+600 seconds'),
            ));
            $output->setRedirectUrl($stepUpSession->returnTo);

            return;
        }
        $signupSession = $this->signupSessionRepository->find($input->state());
        $redirectUrl = $signupSession?->returnTo() ?? self::DEFAULT_REDIRECT_URL;

        $profile = $this->socialOAuthService->fetchProfile($input->provider(), $input->code());
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
            $this->socialLinkingSessionStorageService->issue($existingIdentity->identityIdentifier(), $existingIdentity->email(), $connection, $redirectUrl);
            if ($signupSession !== null) {
                $this->signupSessionRepository->delete($input->state());
            }
            $output->setRedirectUrl('/auth/social/link');

            return;
        }

        $newIdentity = $this->identityFactory->createFromSocialProfile($profile);
        if ($profile->avatarUrl() !== null) {
            try {
                $imagePath = $this->imageService->importFromUrl($profile->avatarUrl());
                $newIdentity->setProfileImage($imagePath);
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

        if ($oneTimeToken = $signupSession?->oneTimeToken()) {
            $this->eventDispatcher->dispatch(new IdentityCreatedViaInvitation(
                identityIdentifier: $newIdentity->identityIdentifier(),
                oneTimeToken: $oneTimeToken,
            ));
        } else {
            $this->eventDispatcher->dispatch(new IdentityCreated(
                identityIdentifier: $newIdentity->identityIdentifier(),
                email: $profile->email(),
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
