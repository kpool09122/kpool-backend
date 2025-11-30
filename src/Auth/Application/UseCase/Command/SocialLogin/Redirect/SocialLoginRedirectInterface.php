<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Redirect;

interface SocialLoginRedirectInterface
{
    public function process(SocialLoginRedirectInputPort $input, SocialLoginRedirectOutputPort $output): void;
}
