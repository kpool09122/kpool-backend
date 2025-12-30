<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterUser;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface RegisterUserInterface
{
    /**
     * @param RegisterUserInputPort $input
     * @return Identity
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     */
    public function process(RegisterUserInputPort $input): Identity;
}
