<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

readonly class SendSocialLinkingEmail implements SendSocialLinkingEmailInterface
{
    public function __construct(private SocialLinkingSessionStorageServiceInterface $socialLinkingSessionStorageService, private IdentityRepositoryInterface $identityRepository)
    {
    }

    /**
     * @throws SocialLinkingSessionInvalidException
     * @throws SocialLinkingVerificationFailedException
     */
    public function process(SendSocialLinkingEmailInputPort $input, SendSocialLinkingEmailOutputPort $output): void
    {
        $session = $this->socialLinkingSessionStorageService->requireValid();
        $identity = $this->identityRepository->findById($session->identityIdentifier);
        if ($identity === null || (string) $identity->email() !== (string) $session->email) {
            throw new SocialLinkingVerificationFailedException();
        }
        $this->socialLinkingSessionStorageService->sendCode($input->language());
        $output->setAccepted(true);
    }
}
