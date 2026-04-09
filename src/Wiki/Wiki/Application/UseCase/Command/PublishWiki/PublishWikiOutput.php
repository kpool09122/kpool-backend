<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use Source\Wiki\Wiki\Domain\Entity\Wiki;

class PublishWikiOutput implements PublishWikiOutputPort
{
    private ?Wiki $wiki = null;

    public function setWiki(Wiki $wiki): void
    {
        $this->wiki = $wiki;
    }

    /**
     * @return array{language: ?string, name: ?string, resourceType: ?string, version: ?int}
     */
    public function toArray(): array
    {
        if ($this->wiki === null) {
            return [
                'language' => null,
                'name' => null,
                'resourceType' => null,
                'version' => null,
            ];
        }

        return [
            'language' => $this->wiki->language()->value,
            'name' => (string) $this->wiki->basic()->name(),
            'resourceType' => $this->wiki->resourceType()->value,
            'version' => $this->wiki->version()->value(),
        ];
    }
}
