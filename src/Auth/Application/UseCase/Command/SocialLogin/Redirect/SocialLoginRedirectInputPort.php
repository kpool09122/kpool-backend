<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Redirect;

use Source\Auth\Domain\ValueObject\SocialProvider;

interface SocialLoginRedirectInputPort
{
    public function provider(): SocialProvider;
}
