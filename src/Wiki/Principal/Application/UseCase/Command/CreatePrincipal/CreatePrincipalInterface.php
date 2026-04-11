<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;

interface CreatePrincipalInterface
{
    /**
     * @param CreatePrincipalInputPort $input
     * @param CreatePrincipalOutputPort $output
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input, CreatePrincipalOutputPort $output): void;
}
