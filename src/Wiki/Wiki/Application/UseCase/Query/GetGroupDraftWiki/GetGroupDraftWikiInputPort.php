<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki;

use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

interface GetGroupDraftWikiInputPort
{
    public function wikiIdentifier(): DraftWikiIdentifier;
}
