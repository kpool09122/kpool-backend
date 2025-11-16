<?php

declare(strict_types=1);

namespace Source\Auth\Application\UseCase\Command\Login;

use DomainException;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Exception\UserNotFoundException;

interface LoginInterface
{
    /**
     * @param LoginInputPort $input
     * @return User
     * @throws UserNotFoundException
     * @throws DomainException
     */
    public function process(LoginInputPort $input): User;
}
