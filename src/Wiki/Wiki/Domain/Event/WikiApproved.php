<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Event;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

readonly class WikiApproved
{
    public function __construct(
        public DraftWikiIdentifier $draftWikiIdentifier,
        public string $wikiName,
        public ResourceType $resourceType,
    ) {
    }
}
