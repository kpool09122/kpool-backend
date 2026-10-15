<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiRejectionReason;

interface WikiWorkflowNotificationServiceInterface
{
    public function notifyApproved(DraftWikiIdentifier $draftWikiIdentifier, string $wikiName): void;

    public function notifyRejected(DraftWikiIdentifier $draftWikiIdentifier, string $wikiName, ResourceType $resourceType, DraftWikiRejectionReason $rejectionReason): void;

    public function notifyPublished(DraftWikiIdentifier $draftWikiIdentifier, string $wikiName, Slug $slug, Language $wikiLanguage): void;
}
