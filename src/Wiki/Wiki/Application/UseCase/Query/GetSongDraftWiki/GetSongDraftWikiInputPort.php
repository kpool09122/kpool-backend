<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki;

use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

interface GetSongDraftWikiInputPort
{
    public function wikiIdentifier(): DraftWikiIdentifier;
}
