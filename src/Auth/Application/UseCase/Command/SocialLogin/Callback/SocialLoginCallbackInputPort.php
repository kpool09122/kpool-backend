<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Callback;

use Source\Auth\Domain\ValueObject\OAuthCode;
use Source\Auth\Domain\ValueObject\OAuthState;
use Source\Auth\Domain\ValueObject\SocialProvider;

interface SocialLoginCallbackInputPort
{
    public function provider(): SocialProvider;

    public function code(): OAuthCode;

    public function state(): OAuthState;
}
