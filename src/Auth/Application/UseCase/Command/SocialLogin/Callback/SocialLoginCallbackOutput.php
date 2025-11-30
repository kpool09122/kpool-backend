<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Callback;

class SocialLoginCallbackOutput implements SocialLoginCallbackOutputPort
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
