<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

class StartPasskeyRecoveryWithSocialOutput implements StartPasskeyRecoveryWithSocialOutputPort
{
    private ?string $redirectUrl = null;

    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    /** @return array{redirectUrl?: string} */
    public function toArray(): array
    {
        return $this->redirectUrl === null ? [] : ['redirectUrl' => $this->redirectUrl];
    }
}
