<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\PublishWikiAtSameVersion;

use Source\Wiki\Wiki\Domain\Entity\Wiki;

interface PublishWikiAtSameVersionOutputPort
{
    public function setWiki(Wiki $wiki): void;

    /**
     * @return array{wikiIdentifier: ?string, language: ?string, name: ?string, resourceType: ?string, version: ?int}
     */
    public function toArray(): array;
}
