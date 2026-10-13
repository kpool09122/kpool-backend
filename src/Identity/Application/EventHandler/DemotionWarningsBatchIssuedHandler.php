<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Shared\Application\Service\AccountNotificationRecipientServiceInterface;
use Source\Wiki\Grading\Domain\Event\DemotionWarningsBatchIssued;

readonly class DemotionWarningsBatchIssuedHandler
{
    public function __construct(
        private AccountNotificationRecipientServiceInterface $accountNotificationRecipientService,
        private CollaboratorNotificationServiceInterface $collaboratorNotificationService,
    ) {
    }

    public function handle(DemotionWarningsBatchIssued $event): void
    {
        foreach ($this->accountNotificationRecipientService->resolve($event->accountIdentifiers()) as $recipient) {
            $this->collaboratorNotificationService->sendDemotionWarning($recipient->email(), $recipient->language());
        }
    }
}
