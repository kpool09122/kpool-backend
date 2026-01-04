<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\Login;

use DomainException;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\IdentityNotFoundException;

interface LoginInterface
{
    /**
     * @param LoginInputPort $input
     * @return Identity
     * @throws IdentityNotFoundException
     * @throws DomainException
     */
    public function process(LoginInputPort $input): Identity;
}
