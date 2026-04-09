<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use Source\Wiki\Wiki\Domain\Entity\Wiki;

interface PublishWikiOutputPort
{
    public function setWiki(Wiki $wiki): void;

    /**
     * @return array{language: ?string, name: ?string, resourceType: ?string, version: ?int}
     */
    public function toArray(): array;
}
