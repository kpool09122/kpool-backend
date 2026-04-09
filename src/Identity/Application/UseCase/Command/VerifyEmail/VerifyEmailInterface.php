<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use Source\Identity\Domain\Exception\AuthCodeExpiredException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\InvalidAuthCodeException;

interface VerifyEmailInterface
{
    /**
     * @param VerifyEmailInputPort $input
     * @param VerifyEmailOutputPort $output
     * @return void
     * @throws AuthCodeSessionNotFoundException
     * @throws AuthCodeExpiredException
     * @throws InvalidAuthCodeException
     */
    public function process(VerifyEmailInputPort $input, VerifyEmailOutputPort $output): void;
}
