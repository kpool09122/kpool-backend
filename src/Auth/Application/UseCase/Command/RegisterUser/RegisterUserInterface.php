<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\RegisterUser;

use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Exception\AlreadyUserExistsException;
use Source\Auth\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface RegisterUserInterface
{
    /**
     * @param RegisterUserInputPort $input
     * @return User
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     */
    public function process(RegisterUserInputPort $input): User;
}
