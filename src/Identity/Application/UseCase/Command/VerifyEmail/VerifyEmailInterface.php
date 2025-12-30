<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\VerifyEmail;

use Source\Identity\Domain\Entity\AuthCodeSession;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;

interface VerifyEmailInterface
{
    /**
     * @param VerifyEmailInputPort $input
     * @return AuthCodeSession
     * @throws AuthCodeSessionNotFoundException
     */
    public function process(VerifyEmailInputPort $input): AuthCodeSession;
}
