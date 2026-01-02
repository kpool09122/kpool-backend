<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Exception\DisallowedChangeRoleException;
use Source\Wiki\Principal\Domain\Exception\OperatorNotFoundException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ChangePrincipalRoleInterface
{
    /**
     * @param ChangePrincipalRoleInputPort $input
     * @return Principal
     * @throws DisallowedChangeRoleException
     * @throws OperatorNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(ChangePrincipalRoleInputPort $input): Principal;
}
