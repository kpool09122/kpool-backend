<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Wiki\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;

interface DeletePrincipalGroupInterface
{
    /**
     * @throws PrincipalGroupNotFoundException
     * @throws CannotDeleteDefaultPrincipalGroupException
     */
    public function process(DeletePrincipalGroupInputPort $input): void;
}
