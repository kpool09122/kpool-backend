<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki;

use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

readonly class GetSongDraftWikiInput implements GetSongDraftWikiInputPort
{
    public function __construct(
        private DraftWikiIdentifier $wikiIdentifier,
    ) {
    }

    public function wikiIdentifier(): DraftWikiIdentifier
    {
        return $this->wikiIdentifier;
    }
}
