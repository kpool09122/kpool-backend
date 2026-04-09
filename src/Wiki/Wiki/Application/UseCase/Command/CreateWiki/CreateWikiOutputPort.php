<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\CreateWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface CreateWikiOutputPort
{
    public function setDraftWiki(DraftWiki $draftWiki): void;

    /**
     * @return array{language: ?string, name: ?string, resourceType: ?string, status: ?string}
     */
    public function toArray(): array;
}
