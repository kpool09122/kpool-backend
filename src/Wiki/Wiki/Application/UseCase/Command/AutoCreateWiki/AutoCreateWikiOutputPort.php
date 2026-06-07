<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface AutoCreateWikiOutputPort
{
    public function setDraftWiki(DraftWiki $draftWiki): void;

    /**
     * @return array{wikiIdentifier: ?string, language: ?string, name: ?string, resourceType: ?string, status: ?string}
     */
    public function toArray(): array;
}
