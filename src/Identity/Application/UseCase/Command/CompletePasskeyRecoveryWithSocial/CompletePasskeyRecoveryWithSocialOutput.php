<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

class CompletePasskeyRecoveryWithSocialOutput implements CompletePasskeyRecoveryWithSocialOutputPort
{
    private ?string $redirectUrl = null;

    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }
}
