<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\VerifyEmail;

use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\Exception\AuthCodeSessionNotFoundException;

interface VerifyEmailInterface
{
    /**
     * @param VerifyEmailInputPort $input
     * @return AuthCodeSession
     * @throws AuthCodeSessionNotFoundException
     */
    public function process(VerifyEmailInputPort $input): AuthCodeSession;
}
