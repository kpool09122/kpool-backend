<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchPromoted;

readonly class PrincipalsBatchPromotedHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private CollaboratorNotificationServiceInterface $notificationService,
    ) {
    }

    public function handle(PrincipalsBatchPromoted $event): void
    {
        $identities = $this->identityRepository->findByIds($event->promotedIdentities());

        foreach ($identities as $identity) {
            $this->notificationService->sendPromotionNotification(
                $identity->email(),
                $identity->language(),
            );
        }
    }
}
