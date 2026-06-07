<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki;

use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

interface GetTalentDraftWikiInputPort
{
    public function wikiIdentifier(): DraftWikiIdentifier;
}
