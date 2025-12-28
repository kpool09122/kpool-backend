<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Wiki\AccessControl\Application\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Entity\Principal;

interface CreatePrincipalInterface
{
    /**
     * @param CreatePrincipalInputPort $input
     * @return Principal
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input): Principal;
}
