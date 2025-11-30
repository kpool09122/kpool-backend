<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Callback;

use Source\Auth\Domain\ValueObject\OAuthCode;
use Source\Auth\Domain\ValueObject\OAuthState;
use Source\Auth\Domain\ValueObject\SocialProvider;

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
