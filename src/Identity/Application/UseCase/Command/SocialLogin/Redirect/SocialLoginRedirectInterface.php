<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Redirect;

use Random\RandomException;

interface SocialLoginRedirectInterface
{
    /**
     * @param SocialLoginRedirectInputPort $input
     * @param SocialLoginRedirectOutputPort $output
     * @return void
     * @throws RandomException
     */
    public function process(SocialLoginRedirectInputPort $input, SocialLoginRedirectOutputPort $output): void;
}
