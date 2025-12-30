<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Callback;

use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;

readonly class SocialLoginCallbackInput implements SocialLoginCallbackInputPort
{
    public function __construct(
        private SocialProvider $provider,
        private OAuthCode $code,
        private OAuthState $state,
    ) {
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function code(): OAuthCode
    {
        return $this->code;
    }

    public function state(): OAuthState
    {
        return $this->state;
    }
}
