<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchPromoted;

readonly class PrincipalsBatchPromotedHandler
{
    public function __construct(
        private AccountNotificationRecipientServiceInterface $accountNotificationRecipientService,
        private CollaboratorNotificationServiceInterface $collaboratorNotificationService,
    ) {
    }

    public function handle(PrincipalsBatchPromoted $event): void
    {
        foreach ($this->accountNotificationRecipientService->resolve($event->accountIdentifiers()) as $recipient) {
            $this->collaboratorNotificationService->sendPromotionNotification($recipient->email(), $recipient->language());
        }
    }
}
