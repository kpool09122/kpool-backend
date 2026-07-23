<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup;

use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;

interface AddPrincipalToPrincipalGroupInterface
{
    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(AddPrincipalToPrincipalGroupInputPort $input, AddPrincipalToPrincipalGroupOutputPort $output): void;
}
