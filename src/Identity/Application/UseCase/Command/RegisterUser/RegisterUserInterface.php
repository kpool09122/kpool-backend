<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RegisterUser;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface RegisterUserInterface
{
    /**
     * @param RegisterUserInputPort $input
     * @return Identity
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedException
     * @throws AlreadyUserExistsException
     * @throws InvalidBase64ImageException
     */
    public function process(RegisterUserInputPort $input): Identity;
}
