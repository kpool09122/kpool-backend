<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface SubmitWikiOutputPort
{
    public function addDraftWiki(DraftWiki $draftWiki): void;

    /**
     * @return list<array{wikiIdentifier: string, language: string, name: string, resourceType: string, status: string}>
     */
    public function toArray(): array;
}
