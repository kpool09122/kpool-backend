<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface SubmitWikiOutputPort
{
    public function setDraftWiki(DraftWiki $draftWiki): void;

    /**
     * @return array{language: ?string, name: ?string, resourceType: ?string, status: ?string}
     */
    public function toArray(): array;
}
