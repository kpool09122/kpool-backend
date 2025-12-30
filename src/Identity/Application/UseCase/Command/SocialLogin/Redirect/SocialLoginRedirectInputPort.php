<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use Source\Identity\Domain\ValueObject\SocialProvider;

interface SocialLoginRedirectInputPort
{
    public function provider(): SocialProvider;
}
