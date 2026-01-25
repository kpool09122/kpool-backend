<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Domain\Event\DemotionWarningsBatchIssued;

readonly class DemotionWarningsBatchIssuedHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private CollaboratorNotificationServiceInterface $notificationService,
    ) {
    }

    public function handle(DemotionWarningsBatchIssued $event): void
    {
        $identities = $this->identityRepository->findByIds($event->warnedIdentities());

        foreach ($identities as $identity) {
            $this->notificationService->sendDemotionWarning(
                $identity->email(),
                $identity->language(),
            );
        }
    }
}
