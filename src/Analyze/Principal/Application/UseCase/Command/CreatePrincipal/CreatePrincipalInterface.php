<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Analyze\Principal\Application\Exception\AccountMembershipRequiredException;
use Source\Analyze\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Analyze\Principal\Domain\Exception\PrincipalAlreadyExistsException;

interface CreatePrincipalInterface
{
    /**
     * @param CreatePrincipalInputPort $inputPort
     */
    /** @throws PrincipalAlreadyExistsException
     * @throws AccountMembershipRequiredException
     * @throws SystemRoleNotFoundException */
    public function process(CreatePrincipalInputPort $inputPort, CreatePrincipalOutputPort $output): void;
}
