<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;

interface CreatePrincipalGroupInterface
{
    public function process(CreatePrincipalGroupInputPort $input): PrincipalGroup;
}
