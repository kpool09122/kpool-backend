<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Account\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Account\Principal\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;

interface DeletePrincipalGroupInterface
{
    /**
     * @throws CannotDeleteDefaultPrincipalGroupException
     * @throws CannotDeleteLastOwnerGroupException
     * @throws PrincipalGroupNotFoundException
     */
    public function process(DeletePrincipalGroupInputPort $input): void;
}
