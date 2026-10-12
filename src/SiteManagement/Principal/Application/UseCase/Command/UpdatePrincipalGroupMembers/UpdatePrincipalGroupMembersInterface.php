<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\SiteManagement\Principal\Application\Exception\CannotRemoveLastAdministratorException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalNotFoundException;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface UpdatePrincipalGroupMembersInterface
{
    /** @throws CannotRemoveLastAdministratorException
     * @throws PrincipalGroupNotFoundException
     * @throws PrincipalNotFoundException
     * @throws UnauthorizedException */
    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void;
}
