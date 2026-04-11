<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

interface CreateInvitationInterface
{
    public function process(CreateInvitationInputPort $input, CreateInvitationOutputPort $output): void;
}
