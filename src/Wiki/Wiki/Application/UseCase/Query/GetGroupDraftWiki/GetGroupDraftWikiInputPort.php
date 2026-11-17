<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

interface GetGroupDraftWikiInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function wikiIdentifier(): DraftWikiIdentifier;
}
