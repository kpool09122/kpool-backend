<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateIdentity;

use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\PasswordMismatchException;
use Source\Identity\Domain\Exception\UnauthorizedEmailException;
use Source\Shared\Application\Exception\InvalidBase64ImageException;

interface CreateIdentityInterface
{
    /**
     * @param CreateIdentityInputPort $input
     * @param CreateIdentityOutputPort $output
     * @return void
     * @throws PasswordMismatchException
     * @throws AuthCodeSessionNotFoundException
     * @throws UnauthorizedEmailException
     * @throws AlreadyUserExistsException
     * @throws InvalidBase64ImageException
     */
    public function process(CreateIdentityInputPort $input, CreateIdentityOutputPort $output): void;
}
