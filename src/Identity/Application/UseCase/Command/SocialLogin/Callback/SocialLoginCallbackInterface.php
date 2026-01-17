<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SocialLogin\Callback;

use Source\Identity\Domain\Exception\InvalidOAuthStateException;

interface SocialLoginCallbackInterface
{
    /**
     * @param SocialLoginCallbackInputPort $input
     * @param SocialLoginCallbackOutputPort $output
     * @return void
     * @throws InvalidOAuthStateException
     */
    public function process(SocialLoginCallbackInputPort $input, SocialLoginCallbackOutputPort $output): void;
}
