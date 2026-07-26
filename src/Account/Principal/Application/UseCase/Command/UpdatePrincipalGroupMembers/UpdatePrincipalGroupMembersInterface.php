<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;

interface UpdatePrincipalGroupMembersInterface
{
    /**
     * @throws AccountUpdateForbiddenException
     * @throws CannotRemoveLastPrincipalGroupManagerException
     * @throws PrincipalGroupNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void;
}
