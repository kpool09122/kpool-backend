<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidCredentialsException;

interface LoginInterface
{
    /**
     * @param LoginInputPort $input
     * @param LoginOutputPort $output
     * @return void
     * @throws IdentityNotFoundException
     * @throws InvalidCredentialsException
     */
    public function process(LoginInputPort $input, LoginOutputPort $output): void;
}
