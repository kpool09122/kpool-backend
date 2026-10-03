<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

interface CompletePasskeyRecoveryWithSocialOutputPort
{
    public function setRedirectUrl(string $redirectUrl): void;
}
