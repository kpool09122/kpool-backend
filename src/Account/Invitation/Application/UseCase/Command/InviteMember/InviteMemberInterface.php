<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\InviteMember;

interface InviteMemberInterface
{
    public function process(InviteMemberInputPort $input, InviteMemberOutputPort $output): void;
}
