<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Application\Mail\WikiWorkflowMail;
use Application\Mail\WikiWorkflowMailType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Service\WikiWorkflowNotificationServiceInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiRejectionReason;

readonly class WikiWorkflowNotificationService implements WikiWorkflowNotificationServiceInterface
{
    public function __construct(
        private WikiHistoryRepositoryInterface $wikiHistoryRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private IdentityRepositoryInterface $identityRepository,
        private string $frontendBaseUrl,
    ) {
    }

    public function notifyApproved(DraftWikiIdentifier $draftWikiIdentifier, string $wikiName): void
    {
        $this->queue($draftWikiIdentifier, $wikiName, WikiWorkflowMailType::APPROVED);
    }

    public function notifyRejected(DraftWikiIdentifier $draftWikiIdentifier, string $wikiName, ResourceType $resourceType, DraftWikiRejectionReason $rejectionReason): void
    {
        $path = '/wiki/' . rawurlencode((string) $draftWikiIdentifier) . '/edit?resourceType=' . rawurlencode($resourceType->value);
        $this->queue($draftWikiIdentifier, $wikiName, WikiWorkflowMailType::REJECTED, $path, (string) $rejectionReason);
    }

    public function notifyPublished(DraftWikiIdentifier $draftWikiIdentifier, string $wikiName, Slug $slug, Language $wikiLanguage): void
    {
        $path = '/' . $wikiLanguage->value . '/wiki/' . rawurlencode((string) $slug);
        $this->queue($draftWikiIdentifier, $wikiName, WikiWorkflowMailType::PUBLISHED, $path);
    }

    private function queue(DraftWikiIdentifier $draftWikiIdentifier, string $wikiName, WikiWorkflowMailType $type, ?string $path = null, ?string $rejectionReason = null): void
    {
        $enqueue = function () use ($draftWikiIdentifier, $wikiName, $type, $path, $rejectionReason): void {
            $submitterIdentifier = $this->wikiHistoryRepository->findLatestSubmissionActor($draftWikiIdentifier);
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
            Mail::to((string) $identity->email())->queue(new WikiWorkflowMail(
                $type,
                $wikiName,
                $path === null ? null : rtrim($this->frontendBaseUrl, '/') . $path,
                $identity->language(),
                $rejectionReason,
            ));
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($enqueue);

            return;
        }
        $enqueue();
    }
}
