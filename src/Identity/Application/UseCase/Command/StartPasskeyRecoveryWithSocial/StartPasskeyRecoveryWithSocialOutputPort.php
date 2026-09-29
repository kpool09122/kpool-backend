<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

interface StartPasskeyRecoveryWithSocialOutputPort
{
    public function setRedirectUrl(string $redirectUrl): void;
}
