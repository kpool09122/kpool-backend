<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use Source\Account\Invitation\Domain\Entity\Invitation;

interface CreateInvitationInterface
{
    /**
     * @return array<Invitation>
     */
    public function process(CreateInvitationInputPort $input): array;
}
