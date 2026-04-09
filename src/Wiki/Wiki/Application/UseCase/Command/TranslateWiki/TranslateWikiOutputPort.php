<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

interface TranslateWikiOutputPort
{
    /**
     * @param DraftWiki[] $draftWikis
     */
    public function setDraftWikis(array $draftWikis): void;

    /**
     * @return array{draftWikis: array<int, array{language: string, name: string, resourceType: string, status: string}>}
     */
    public function toArray(): array;
}
