<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use Source\Wiki\Wiki\Domain\Entity\Wiki;

interface RollbackWikiOutputPort
{
    /**
     * @param Wiki[] $wikis
     */
    public function setWikis(array $wikis): void;

    /**
     * @return array{wikis: array<int, array{language: string, name: string, resourceType: string, version: int}>}
     */
    public function toArray(): array;
}
