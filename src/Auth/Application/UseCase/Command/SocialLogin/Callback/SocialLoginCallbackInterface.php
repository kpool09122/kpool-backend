<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\SocialLogin\Callback;

interface SocialLoginCallbackInterface
{
    public function process(SocialLoginCallbackInputPort $input, SocialLoginCallbackOutputPort $output): void;
}
