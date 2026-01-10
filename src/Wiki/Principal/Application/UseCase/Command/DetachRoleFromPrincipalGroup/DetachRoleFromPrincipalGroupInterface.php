<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;

interface DetachRoleFromPrincipalGroupInterface
{
    /**
     * @throws PrincipalGroupNotFoundException
     */
    public function process(DetachRoleFromPrincipalGroupInputPort $input): void;
}
