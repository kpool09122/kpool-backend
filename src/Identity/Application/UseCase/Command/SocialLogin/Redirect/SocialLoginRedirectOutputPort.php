<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

interface SocialLoginRedirectOutputPort
{
    public function setRedirectUrl(string $redirectUrl): void;

    public function redirectUrl(): ?string;
}
