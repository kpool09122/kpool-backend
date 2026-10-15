<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Application\Mail\WikiWorkflowMail;
use Application\Mail\WikiWorkflowMailType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Wiki\Application\Service\WikiWorkflowNotificationServiceInterface;
use Source\Wiki\Wiki\Domain\Event\WikiApproved;
use Source\Wiki\Wiki\Domain\Event\WikiPublished;
use Source\Wiki\Wiki\Domain\Event\WikiRejected;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;

readonly class WikiWorkflowNotificationService implements WikiWorkflowNotificationServiceInterface
{
    public function __construct(
        private WikiHistoryRepositoryInterface $wikiHistoryRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private IdentityRepositoryInterface $identityRepository,
        private string $frontendBaseUrl,
    ) {
    }

    public function notify(WikiApproved|WikiRejected|WikiPublished $event): void
    {
        $send = function () use ($event): void {
            $submitterIdentifier = $this->wikiHistoryRepository->findLatestSubmissionActor($event->draftWikiIdentifier);
            if ($submitterIdentifier === null) {
                return;
            }
            $principal = $this->principalRepository->findById($submitterIdentifier);
            if ($principal === null) {
                return;
            }
            $identity = $this->identityRepository->findById($principal->identityIdentifier());
            if ($identity === null) {
                return;
            }
            $type = match (true) {
                $event instanceof WikiApproved => WikiWorkflowMailType::APPROVED,
                $event instanceof WikiRejected => WikiWorkflowMailType::REJECTED,
                $event instanceof WikiPublished => WikiWorkflowMailType::PUBLISHED,
            };
            $path = match (true) {
                $event instanceof WikiApproved => null,
                $event instanceof WikiPublished => '/' . $event->wikiLanguage->value . '/wiki/' . rawurlencode((string) $event->slug),
                $event instanceof WikiRejected => '/wiki/' . rawurlencode((string) $event->draftWikiIdentifier) . '/edit?resourceType=' . rawurlencode($event->resourceType->value),
            };
            Mail::to((string) $identity->email())->send(new WikiWorkflowMail(
                $type,
                $event->wikiName,
                $path === null ? null : rtrim($this->frontendBaseUrl, '/') . $path,
                $identity->language(),
                $event instanceof WikiRejected ? (string) $event->rejectionReason : null,
            ));
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($send);

            return;
        }
        $send();
    }
}
