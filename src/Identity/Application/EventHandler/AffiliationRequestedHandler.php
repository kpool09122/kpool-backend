<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Account\Affiliation\Domain\Event\AffiliationRequested;
use Source\Identity\Application\Service\AffiliationRequestNotificationServiceInterface;

readonly class AffiliationRequestedHandler
{
    public function __construct(
        private AffiliationRequestNotificationServiceInterface $notificationService,
    ) {
    }

    public function handle(AffiliationRequested $event): void
    {
        $this->notificationService->sendAffiliationRequestNotification($event->targetEmail);
    }
}
