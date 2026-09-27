<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSessionStorageServiceInterface;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySessionStorageServiceInterface;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Repository\OAuthStateRepositoryInterface;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\Service\SocialOAuthServiceInterface;
use Source\Identity\Domain\ValueObject\SocialConnection;

readonly class CompletePasskeyRecoveryWithSocial implements CompletePasskeyRecoveryWithSocialInterface
{
    private const string RETURN_TO = '/settings/passkeys/recovery?recoveryKey=';

    public function __construct(
        private OAuthStateRepositoryInterface $stateRepository,
        private PasskeyRecoveryOAuthSessionStorageServiceInterface $oauthSessions,
        private SocialOAuthServiceInterface $socialOAuthService,
        private IdentityRepositoryInterface $identityRepository,
        private PasskeyCredentialRepositoryInterface $passkeyCredentialRepository,
        private PasskeyRecoverySessionStorageServiceInterface $recoverySessions,
    ) {
    }

    public function process(
        CompletePasskeyRecoveryWithSocialInputPort $input,
        CompletePasskeyRecoveryWithSocialOutputPort $output,
    ): void {
        $this->stateRepository->consume($input->state());
        $oauthSession = $this->oauthSessions->consume($input->state());
        if ($oauthSession === null || $oauthSession->provider !== $input->provider()) {
            throw new PasskeyRecoveryVerificationFailedException('Passkey recovery OAuth session is invalid.');
        }

        $profile = $this->socialOAuthService->fetchProfile($input->provider(), $input->code());
        $connection = new SocialConnection($input->provider(), $profile->providerUserId());
        $identity = $this->identityRepository->findBySocialConnection(
            $connection->provider(),
            $connection->providerUserId(),
        );
        if ($identity === null
            || $this->passkeyCredentialRepository->findByIdentityIdentifier($identity->identityIdentifier()) === []) {
            throw new PasskeyRecoveryVerificationFailedException('The social account is not linked or has no passkey to recover.');
        }

        $recoveryKey = $this->recoverySessions->issue($identity->identityIdentifier(), 'sso');
        $output->setRedirectUrl(self::RETURN_TO . rawurlencode((string) $recoveryKey));
    }
}
