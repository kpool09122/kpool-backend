<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Callback;

interface SocialLoginCallbackOutputPort
{
    public function setRedirectUrl(string $redirectUrl): void;

    public function redirectUrl(): ?string;
}
