<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchDemoted;

readonly class PrincipalsBatchDemotedHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private CollaboratorNotificationServiceInterface $notificationService,
    ) {
    }

    public function handle(PrincipalsBatchDemoted $event): void
    {
        $identities = $this->identityRepository->findByIds($event->demotedIdentities());

        foreach ($identities as $identity) {
            $this->notificationService->sendDemotionNotification(
                $identity->email(),
                $identity->language(),
            );
        }
    }
}
