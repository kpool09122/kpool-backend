<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\GetGroupDraftWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

readonly class GetGroupDraftWikiInput implements GetGroupDraftWikiInputPort
{
    public function __construct(
        private DraftWikiIdentifier $wikiIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function wikiIdentifier(): DraftWikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
