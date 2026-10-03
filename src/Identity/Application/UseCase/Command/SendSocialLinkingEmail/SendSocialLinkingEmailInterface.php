<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;

interface SendSocialLinkingEmailInterface
{
    /**
     * @throws SocialLinkingSessionInvalidException
     * @throws SocialLinkingVerificationFailedException
     */
    public function process(SendSocialLinkingEmailInputPort $input, SendSocialLinkingEmailOutputPort $output): void;
}
