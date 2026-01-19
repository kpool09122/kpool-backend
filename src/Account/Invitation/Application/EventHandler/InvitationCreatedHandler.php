<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Application\EventHandler;

use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\Service\InvitationMailServiceInterface;

readonly class InvitationCreatedHandler
{
    public function __construct(
        private InvitationRepositoryInterface $invitationRepository,
        private InvitationMailServiceInterface $invitationMailService,
    ) {
    }

    public function handle(InvitationCreated $event): void
    {
        $invitation = $this->invitationRepository->findByToken($event->token);

        if ($invitation === null) {
            return;
        }

        $this->invitationMailService->sendInvitationEmail($invitation);
    }
}
