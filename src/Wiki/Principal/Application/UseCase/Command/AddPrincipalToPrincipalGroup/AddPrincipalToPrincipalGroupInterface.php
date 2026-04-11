<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;

interface AddPrincipalToPrincipalGroupInterface
{
    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(AddPrincipalToPrincipalGroupInputPort $input, AddPrincipalToPrincipalGroupOutputPort $output): void;
}
