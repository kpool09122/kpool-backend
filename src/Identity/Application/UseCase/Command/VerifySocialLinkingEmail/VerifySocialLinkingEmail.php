<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\SocialConnectionAlreadyExistsException;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;

readonly class VerifySocialLinkingEmail implements VerifySocialLinkingEmailInterface
{
    public function __construct(
        private SocialLinkingSessionStorageServiceInterface $socialLinkingSessionStorageService,
        private IdentityRepositoryInterface $identityRepository,
        private AuthServiceInterface $authService,
    ) {
    }

    /**
     * @throws SocialLinkingSessionInvalidException
     * @throws SocialLinkingVerificationFailedException
     */
    public function process(VerifySocialLinkingEmailInputPort $input, VerifySocialLinkingEmailOutputPort $output): void
    {
        $session = $this->socialLinkingSessionStorageService->verifyAndConsume($input->code());
        $identity = $this->identityRepository->findById($session->identityIdentifier);
        if ($identity === null || (string) $identity->email() !== (string) $session->email) {
            throw new SocialLinkingVerificationFailedException();
        }
        $owner = $this->identityRepository->findBySocialConnection($session->connection->provider(), $session->connection->providerUserId());
        if ($owner !== null) {
            throw new SocialLinkingVerificationFailedException();
        }

        try {
            $identity->addSocialConnection($session->connection);
        } catch (SocialConnectionAlreadyExistsException) {
            throw new SocialLinkingVerificationFailedException();
        }
        $this->identityRepository->save($identity);
        $this->authService->login($identity);
        $output->setRedirectUrl($session->returnTo);
    }
}
