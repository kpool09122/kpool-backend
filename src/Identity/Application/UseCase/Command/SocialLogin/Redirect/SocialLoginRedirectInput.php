<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\SocialProvider;

readonly class SocialLoginRedirectInput implements SocialLoginRedirectInputPort
{
    public function __construct(
        private SocialProvider $provider,
        private ?SignupSession $signupSession = null,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function signupSession(): ?SignupSession
    {
        return $this->signupSession;
    }
}
