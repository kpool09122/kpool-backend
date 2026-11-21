<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Monetization\Principal\Application\Exception\AccountMembershipRequiredException;
use Source\Monetization\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Monetization\Principal\Domain\Exception\PrincipalAlreadyExistsException;

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
