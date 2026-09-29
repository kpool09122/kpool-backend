<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifySocialLinkingEmail;

interface VerifySocialLinkingEmailOutputPort
{
    public function setRedirectUrl(string $redirectUrl): void;
}
