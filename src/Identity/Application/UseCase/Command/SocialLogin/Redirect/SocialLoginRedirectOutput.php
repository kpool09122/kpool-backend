<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

class SocialLoginRedirectOutput implements SocialLoginRedirectOutputPort
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
