<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Event;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

readonly class WikiPublished
{
    public function __construct(
        public DraftWikiIdentifier $draftWikiIdentifier,
        public string $wikiName,
        public ResourceType $resourceType,
        public Slug $slug,
        public Language $wikiLanguage,
    ) {
    }
}
