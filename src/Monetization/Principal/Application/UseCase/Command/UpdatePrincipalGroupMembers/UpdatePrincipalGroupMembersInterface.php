<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use InvalidArgumentException;
use Source\Monetization\Principal\Application\Exception\CannotRemoveLastMonetizationAdministratorException;
use Source\Monetization\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Monetization\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Monetization\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface UpdatePrincipalGroupMembersInterface
{
    /**
     * @throws UnauthorizedException
     * @throws InvalidArgumentException
     * @throws PrincipalGroupNotFoundException
     * @throws PrincipalNotFoundException
     * @throws PrincipalStatusChangeForbiddenException
     * @throws CannotRemoveLastMonetizationAdministratorException
     */
    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void;
}
