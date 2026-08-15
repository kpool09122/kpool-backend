<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

interface UpdatePrincipalGroupMembersInterface
{
    public function process(UpdatePrincipalGroupMembersInputPort $input, UpdatePrincipalGroupMembersOutputPort $output): void;
}
