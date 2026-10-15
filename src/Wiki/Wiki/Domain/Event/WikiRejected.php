<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Event;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiRejectionReason;

readonly class WikiRejected
{
    public function __construct(
        public DraftWikiIdentifier $draftWikiIdentifier,
        public string $wikiName,
        public ResourceType $resourceType,
        public DraftWikiRejectionReason $rejectionReason,
    ) {
    }
}
