<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Redirect;

use Source\Auth\Domain\ValueObject\SocialProvider;

readonly class SocialLoginRedirectInput implements SocialLoginRedirectInputPort
{
    public function __construct(
        private SocialProvider $provider,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }
}
