<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;

interface RemovePrincipalFromPrincipalGroupInterface
{
    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(RemovePrincipalFromPrincipalGroupInputPort $input, RemovePrincipalFromPrincipalGroupOutputPort $output): void;
}
