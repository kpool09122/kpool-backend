<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;

interface VerifySocialLinkingEmailInterface
{
    /**
     * @throws SocialLinkingSessionInvalidException
     * @throws SocialLinkingVerificationFailedException
     */
    public function process(VerifySocialLinkingEmailInputPort $input, VerifySocialLinkingEmailOutputPort $output): void;
}
